<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Faculty;
use App\Models\Subject;
use App\Services\DocumentFormService;
use App\Services\FileService;

class DocumentManageController extends Controller
{
    private const MANAGEABLE_STATUSES = ['approved', 'pending', 'rejected', 'hidden'];

    private Document $documentModel;
    private DocumentFormService $formService;
    private FileService $fileService;

    public function __construct()
    {
        $this->documentModel = new Document();
        $this->formService   = new DocumentFormService();
        $this->fileService   = new FileService();
    }

    public function index(array $params = []): void
    {
        $this->requireRole('admin', 'moderator');

        $page      = max(1, (int)($_GET['page'] ?? 1));
        $perPage   = 20;
        $status    = trim($_GET['status'] ?? '');
        $subjectId = max(0, (int)($_GET['subject_id'] ?? 0));
        $typeId    = max(0, (int)($_GET['type_id'] ?? 0));
        $allowedStatuses = ['approved', 'pending', 'rejected', 'hidden', 'draft', 'archived'];
        $validStatus = in_array($status, $allowedStatuses, true) ? $status : '';

        $filters = [
            'query'      => trim($_GET['q'] ?? ''),
            'status'     => $validStatus,
            'subject_id' => $subjectId,
            'type_id'    => $typeId,
            'sort'       => 'newest',
        ];

        $searchFilters = [
            'query'      => $filters['query'],
            'status'     => $validStatus !== '' ? $validStatus : 'all',
            'subject_id' => $subjectId > 0 ? $subjectId : null,
            'type_id'    => $typeId > 0 ? $typeId : null,
            'sort'       => 'newest',
        ];

        $allDocs  = $this->documentModel->search($searchFilters, $page, $perPage);
        $subjects = (new Subject())->getAllForAdmin();
        $types    = (new DocumentType())->getAllForAdmin();

        $this->view('admin.documents.index', [
            'title'    => 'Quản lý tài liệu — Admin TMU',
            'docs'     => $allDocs,
            'filters'  => $filters,
            'subjects' => $subjects,
            'types'    => $types,
        ], 'admin');
    }

    public function create(array $params = []): void
    {
        $this->requireRole('admin', 'moderator');

        $this->view('admin.documents.create', [
            'title'     => 'Đăng tải tài liệu mới — Admin TMU',
            'faculties' => (new Faculty())->getAllForAdmin(),
            'subjects'  => (new Subject())->getAllForAdmin(),
            'types'     => (new DocumentType())->getAllForAdmin(),
        ], 'admin');
    }

    public function store(array $params = []): void
    {
        $this->requireRole('admin', 'moderator');
        $this->csrfValidate();

        [$data, $errors] = $this->formService->extractAndValidateInput($_POST);
        $status = trim((string)($_POST['status'] ?? 'approved'));
        if (!in_array($status, self::MANAGEABLE_STATUSES, true)) {
            $status = 'approved';
        }
        $rejectionReason = trim((string)($_POST['rejection_reason'] ?? ''));
        if ($status === 'rejected' && $rejectionReason === '') {
            $errors['rejection_reason'][] = 'Vui lòng nhập lý do từ chối khi chọn trạng thái Bị từ chối.';
        }

        if (empty($_FILES['document_file']['name'])) {
            $errors['document_file'][] = 'Vui lòng chọn file tài liệu.';
        }

        $dupErrors = $this->formService->checkDuplicateErrors($this->documentModel, $data, $_FILES['document_file'] ?? null);
        foreach ($dupErrors as $field => $msgs) {
            $errors[$field] = array_merge($errors[$field] ?? [], $msgs);
        }

        if (!empty($errors)) {
            storeOldInput();
            flash('errors', serialize($errors));
            $this->redirect('/admin/documents/create');
        }

        $fileResult = $this->fileService->uploadDocument($_FILES['document_file']);
        if (!$fileResult['success']) {
            storeOldInput();
            flash('error', $fileResult['error']);
            $this->redirect('/admin/documents/create');
        }

        if ($status === 'approved') {
            $this->fileService->moveToApproved($fileResult['file_path']);
        }

        try {
            $this->documentModel->create([
                'user_id'           => userId(),
                'subject_id'        => $data['subject_id'],
                'document_type_id'  => $data['document_type_id'],
                'title'             => $data['title'],
                'slug'              => slug($data['title']) . '-' . uniqid(),
                'description'       => $data['description'],
                'file_path'         => $fileResult['file_path'],
                'original_filename' => $fileResult['original_filename'],
                'mime_type'         => $fileResult['mime_type'],
                'file_size'         => $fileResult['file_size'],
                'checksum'          => $fileResult['checksum'],
                'status'            => $status,
                'rejection_reason'  => $status === 'rejected' ? $rejectionReason : null,
                'approved_by'       => $status === 'approved' ? userId() : null,
                'approved_at'       => $status === 'approved' ? date('Y-m-d H:i:s') : null,
            ]);

            clearOldInput();
            flash('success', 'Đã thêm tài liệu và lưu tệp tin thành công.');
            $this->redirect('/admin/documents');
        } catch (\Throwable $e) {
            if (!$this->documentModel->isFilePathInUse($fileResult['file_path'])) {
                $this->fileService->deleteFile($fileResult['file_path']);
            }
            storeOldInput();
            flash('error', 'Lỗi khi lưu tài liệu: ' . $e->getMessage());
            $this->redirect('/admin/documents/create');
        }
    }

    public function edit(array $params = []): void
    {
        $this->requireRole('admin', 'moderator');

        $doc = $this->documentModel->getWithDetails((int)$params['id']);
        if (!$doc) {
            flash('error', 'Tài liệu không tồn tại.');
            $this->redirect('/admin/documents');
        }

        $this->view('admin.documents.edit', [
            'title'     => 'Chỉnh sửa tài liệu — Admin TMU',
            'doc'       => $doc,
            'faculties' => (new Faculty())->getAllForAdmin(),
            'subjects'  => (new Subject())->getAllForAdmin(),
            'types'     => (new DocumentType())->getAllForAdmin(),
        ], 'admin');
    }

    public function update(array $params = []): void
    {
        $this->requireRole('admin', 'moderator');
        $this->csrfValidate();

        $docId = (int)$params['id'];
        $doc   = $this->documentModel->findById($docId);
        if (!$doc || !empty($doc['deleted_at'])) {
            flash('error', 'Tài liệu không tồn tại.');
            $this->redirect('/admin/documents');
        }

        [$data, $errors] = $this->formService->extractAndValidateInput($_POST);
        $status = trim((string)($_POST['status'] ?? $doc['status']));
        if (!in_array($status, self::MANAGEABLE_STATUSES, true)) {
            $status = (string)$doc['status'];
        }
        $rejectionReason = trim((string)($_POST['rejection_reason'] ?? ''));
        if ($status === 'rejected' && $rejectionReason === '') {
            $errors['rejection_reason'][] = 'Vui lòng nhập lý do từ chối khi chọn trạng thái Bị từ chối.';
        }

        $uploadedFile = !empty($_FILES['document_file']['name']) ? $_FILES['document_file'] : null;
        $dupErrors    = $this->formService->checkDuplicateErrors($this->documentModel, $data, $uploadedFile, $docId);
        foreach ($dupErrors as $field => $msgs) {
            $errors[$field] = array_merge($errors[$field] ?? [], $msgs);
        }

        if (!empty($errors)) {
            storeOldInput();
            flash('errors', serialize($errors));
            $this->redirect('/admin/documents/' . $docId . '/edit');
        }

        if ($uploadedFile !== null) {
            $fileResult = $this->fileService->uploadDocument($uploadedFile);
            if (!$fileResult['success']) {
                storeOldInput();
                flash('error', $fileResult['error']);
                $this->redirect('/admin/documents/' . $docId . '/edit');
            }

            if (!empty($doc['file_path'])) {
                if ($doc['file_path'] !== $fileResult['file_path']) {
                    if (!$this->documentModel->isFilePathInUse($doc['file_path'], $docId)) {
                        $this->fileService->deleteFile($doc['file_path']);
                    }
                } elseif (!$this->documentModel->isFilePathInUse($doc['file_path'], $docId, 'approved')) {
                    $this->fileService->deleteFile($doc['file_path'], 'approved');
                }
            }

            $data['file_path']         = $fileResult['file_path'];
            $data['original_filename'] = $fileResult['original_filename'];
            $data['mime_type']         = $fileResult['mime_type'];
            $data['file_size']         = $fileResult['file_size'];
            $data['checksum']          = $fileResult['checksum'];
        }

        $activeFilePath = (string)($data['file_path'] ?? $doc['file_path'] ?? '');
        if ($activeFilePath !== '') {
            if ($status === 'approved') {
                $this->fileService->moveToApproved($activeFilePath);
            } elseif (in_array($status, ['pending', 'rejected'], true) && $uploadedFile === null) {
                $keepApproved = $this->documentModel->isFilePathInUse($activeFilePath, $docId, 'approved');
                $this->fileService->moveToPending($activeFilePath, $keepApproved);
            }
        }

        $updatePayload = array_merge($data, [
            'slug'             => slug($data['title']) . '-' . $docId,
            'status'           => $status,
            'rejection_reason' => $status === 'rejected' ? $rejectionReason : null,
        ]);

        if ($status === 'approved' && ($doc['status'] ?? '') !== 'approved') {
            $updatePayload['approved_by'] = userId();
            $updatePayload['approved_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'pending') {
            $updatePayload['approved_by'] = null;
            $updatePayload['approved_at'] = null;
        }

        $this->documentModel->update($docId, $updatePayload);

        clearOldInput();
        flash('success', 'Đã cập nhật thông tin và tệp tài liệu thành công.');
        $this->redirect('/admin/documents');
    }

    public function preview(array $params = []): void
    {
        $this->requireRole('admin', 'moderator');

        $doc = $this->documentModel->getWithDetails((int)$params['id']);
        if (!$doc) {
            http_response_code(404);
            die('Tài liệu không tồn tại.');
        }

        $filePath = $this->fileService->resolveDocumentPath((string)$doc['file_path']);
        if ($filePath === null) {
            http_response_code(404);
            die('File không tồn tại trên server.');
        }

        $contentType = $this->fileService->resolvePreviewContentType(
            (string)$doc['original_filename'],
            (string)$doc['mime_type']
        );
        $this->fileService->streamDocument($filePath, (string)$doc['original_filename'], $contentType, true);
    }

    public function download(array $params = []): void
    {
        $this->requireRole('admin', 'moderator');

        $doc = $this->documentModel->getWithDetails((int)$params['id']);
        if (!$doc) {
            flash('error', 'Tài liệu không tồn tại.');
            $this->redirect('/admin/documents');
        }

        $filePath = $this->fileService->resolveDocumentPath((string)$doc['file_path']);
        if ($filePath === null) {
            flash('error', 'File không tồn tại trên server.');
            $this->redirect('/admin/documents');
        }

        $this->fileService->streamDocument($filePath, (string)$doc['original_filename'], (string)$doc['mime_type'], false);
    }

    public function destroy(array $params = []): void
    {
        $this->requireRole('admin', 'moderator');
        $this->csrfValidate();

        $docId = (int)$params['id'];
        $doc   = $this->documentModel->findById($docId);

        if ($doc && empty($doc['deleted_at'])) {
            $this->documentModel->softDelete($docId);
            if (!empty($doc['file_path']) && !$this->documentModel->isFilePathInUse($doc['file_path'], $docId)) {
                $this->fileService->deleteFile($doc['file_path']);
            }
            flash('success', 'Tài liệu đã được xóa.');
        }

        $this->redirect('/admin/documents');
    }
}
