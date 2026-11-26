<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Config;
use App\Models\Document;
use App\Models\Faculty;
use App\Models\Subject;
use App\Models\DocumentType;
use App\Models\Comment;
use App\Models\Favorite;
use App\Services\DocumentFormService;
use App\Services\FileService;

class DocumentController extends Controller
{
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
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = Config::get('pagination.per_page', 12);

        $filters = [
            'query'       => trim($_GET['q'] ?? ''),
            'faculty_id'  => (int)($_GET['faculty'] ?? 0) ?: null,
            'subject_id'  => (int)($_GET['subject'] ?? 0) ?: null,
            'type_id'     => (int)($_GET['type'] ?? 0) ?: null,
            'sort'        => $_GET['sort'] ?? 'newest',
        ];

        $facultyModel = new Faculty();
        $subjectModel = new Subject();
        $typeModel    = new DocumentType();

        $this->view('documents.index', [
            'title'     => 'Tài liệu học tập — TMU Tài Liệu',
            'docs'      => $this->documentModel->search(array_filter($filters, fn($v) => $v !== null), $page, $perPage),
            'faculties' => $facultyModel->getActive(),
            'subjects'  => $subjectModel->getActive(),
            'types'     => $typeModel->getActive(),
            'filters'   => $filters,
        ]);
    }

    public function show(array $params = []): void
    {
        // Chặn người dùng chưa đăng nhập (khách) xem chi tiết tài liệu
        if (!auth()) {
            flash('error', 'Vui lòng đăng nhập để xem chi tiết tài liệu và tải file.');
            $returnUrl = '/documents/' . (int)($params['id'] ?? 0);
            $this->redirect('/login?return=' . urlencode($returnUrl));
            return;
        }

        $doc = $this->documentModel->getWithDetails((int)$params['id']);
        if (!$doc) {
            http_response_code(404);
            $this->view('errors.404', ['title' => 'Không tìm thấy tài liệu']);
            return;
        }

        // Only approved docs are public (unless owner or admin/moderator)
        if ($doc['status'] !== 'approved') {
            $user = currentUser();
            if ($user['id'] != $doc['user_id'] && !isModerator()) {
                http_response_code(403);
                $this->view('errors.403', ['title' => 'Truy cập bị từ chối']);
                return;
            }
        }

        // Increment view count
        $this->documentModel->incrementView((int)$params['id']);

        $commentModel  = new Comment();
        $favoriteModel = new Favorite();

        $isFavorited = auth() ? $favoriteModel->isFavorited(userId(), $doc['id']) : false;

        $this->view('documents.show', [
            'title'       => $doc['title'] . ' — TMU Tài Liệu',
            'doc'         => $doc,
            'comments'    => $commentModel->getByDocument($doc['id']),
            'isFavorited' => $isFavorited,
        ]);
    }

    public function preview(array $params = []): void
    {
        if (!auth()) {
            http_response_code(403);
            die('Vui lòng đăng nhập để xem trước tài liệu.');
        }

        $doc = $this->documentModel->getWithDetails((int)$params['id']);
        if (!$doc || $doc['status'] !== 'approved') {
            http_response_code(404);
            die('Tài liệu không tồn tại hoặc chưa được duyệt.');
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
        $doc = $this->documentModel->getWithDetails((int)$params['id']);
        if (!$doc || $doc['status'] !== 'approved') {
            http_response_code(404);
            die('Tài liệu không tồn tại hoặc chưa được duyệt.');
        }

        // Members only
        if (!auth()) {
            flash('error', 'Vui lòng đăng nhập để tải tài liệu.');
            $this->redirect('/login');
        }

        $filePath = $this->fileService->resolveDocumentPath((string)$doc['file_path']);
        if ($filePath === null) {
            flash('error', 'File không tồn tại trên server. Vui lòng liên hệ quản trị viên.');
            $this->redirect('/documents/' . $doc['id']);
        }

        // Record download
        $this->documentModel->incrementDownload($doc['id']);
        $histModel = new \App\Models\DownloadHistory();
        $histModel->record($doc['id'], userId());

        $this->fileService->streamDocument($filePath, (string)$doc['original_filename'], (string)$doc['mime_type'], false);
    }

    public function create(array $params = []): void
    {
        $this->requireLogin();

        $facultyModel = new Faculty();
        $typeModel    = new DocumentType();

        $this->view('documents.create', [
            'title'     => 'Đăng tải tài liệu — TMU Tài Liệu',
            'faculties' => $facultyModel->getActive(),
            'subjects'  => (new Subject())->getActive(),
            'types'     => $typeModel->getActive(),
        ]);
    }

    private function extractAndValidateDocumentInput(): array
    {
        return $this->formService->extractAndValidateInput($_POST);
    }

    private function checkDuplicateErrors(array $data, ?array $file, int $excludeId = 0): array
    {
        return $this->formService->checkDuplicateErrors($this->documentModel, $data, $file, $excludeId);
    }

    public function store(array $params = []): void
    {
        $this->requireLogin();
        $this->csrfValidate();

        [$data, $errors] = $this->extractAndValidateDocumentInput();

        // Validate file
        if (empty($_FILES['document_file']['name'])) {
            $errors['document_file'][] = 'Vui lòng chọn file tài liệu.';
        }

        $dupErrors = $this->checkDuplicateErrors($data, $_FILES['document_file'] ?? null);
        foreach ($dupErrors as $field => $msgs) {
            $errors[$field] = array_merge($errors[$field] ?? [], $msgs);
        }

        if (!empty($errors)) {
            storeOldInput();
            flash('errors', serialize($errors));
            $this->redirect('/documents/create');
        }

        $fileService = new FileService();
        $fileResult  = $fileService->uploadDocument($_FILES['document_file']);

        if (!$fileResult['success']) {
            storeOldInput();
            flash('error', $fileResult['error']);
            $this->redirect('/documents/create');
        }

        $autoApprove = isModerator();
        $status      = $autoApprove ? 'approved' : 'pending';
        $approvedBy  = $autoApprove ? userId() : null;
        $approvedAt  = $autoApprove ? date('Y-m-d H:i:s') : null;

        if ($autoApprove) {
            try {
                $fileService->moveToApproved($fileResult['file_path']);
            } catch (\Throwable $e) {
                error_log("Failed to auto-move file to approved: " . $e->getMessage());
            }
        }

        try {
            $docId = $this->documentModel->create([
                'user_id'          => userId(),
                'subject_id'       => $data['subject_id'],
                'document_type_id' => $data['document_type_id'],
                'title'            => $data['title'],
                'slug'             => slug($data['title']) . '-' . uniqid(),
                'description'      => $data['description'],
                'file_path'        => $fileResult['file_path'],
                'original_filename'=> $fileResult['original_filename'],
                'mime_type'        => $fileResult['mime_type'],
                'file_size'        => $fileResult['file_size'],
                'checksum'         => $fileResult['checksum'],
                'status'           => $status,
                'approved_by'      => $approvedBy,
                'approved_at'      => $approvedAt,
            ]);

            clearOldInput();
            if ($autoApprove) {
                flash('success', 'Tài liệu đã được đăng tải và công khai trực tiếp lên hệ thống!');
                $this->redirect('/documents/' . $docId);
            } else {
                flash('success', 'Tài liệu đã được gửi thành công! Vui lòng chờ kiểm duyệt viên phê duyệt.');
                $this->redirect('/my-documents');
            }
        } catch (\Throwable $e) {
            if (!$this->documentModel->isFilePathInUse($fileResult['file_path'])) {
                $fileService->deleteFile($fileResult['file_path']);
            }
            storeOldInput();
            flash('error', 'Lỗi khi lưu dữ liệu: ' . $e->getMessage());
            $this->redirect('/documents/create');
        }
    }

    public function edit(array $params = []): void
    {
        $this->requireLogin();
        $doc = $this->documentModel->getWithDetails((int)$params['id']);

        if (!$doc) {
            $this->redirect('/my-documents');
        }

        // Only admin, or owner while document is still pending approval
        if (userId() !== (int)$doc['user_id'] && !isAdmin()) {
            http_response_code(403);
            $this->view('errors.403', ['title' => 'Từ chối truy cập']);
            return;
        }
        if (!isAdmin() && ($doc['status'] ?? '') !== 'pending') {
            flash('error', 'Tài liệu đã qua kiểm duyệt, thành viên không thể chỉnh sửa.');
            $this->redirect('/my-documents');
        }

        $this->view('documents.edit', [
            'title'     => 'Chỉnh sửa tài liệu — TMU Tài Liệu',
            'doc'       => $doc,
            'faculties' => (new Faculty())->getActive(),
            'subjects'  => (new Subject())->getActive(),
            'types'     => (new DocumentType())->getActive(),
        ]);
    }

    public function update(array $params = []): void
    {
        $this->requireLogin();
        $this->csrfValidate();

        $doc = $this->documentModel->findById((int)$params['id']);
        if (!$doc || !empty($doc['deleted_at'])) {
            flash('error', 'Tài liệu không tồn tại.');
            $this->redirect('/my-documents');
        }
        if (userId() !== (int)$doc['user_id'] && !isAdmin()) {
            http_response_code(403);
            die('Từ chối truy cập.');
        }
        if (!isAdmin() && ($doc['status'] ?? '') !== 'pending') {
            flash('error', 'Tài liệu đã qua kiểm duyệt, thành viên không thể chỉnh sửa.');
            $this->redirect('/my-documents');
        }

        [$data, $errors] = $this->extractAndValidateDocumentInput();
        $uploadedFile = !empty($_FILES['document_file']['name']) ? $_FILES['document_file'] : null;
        $dupErrors = $this->checkDuplicateErrors($data, $uploadedFile, (int)$doc['id']);
        foreach ($dupErrors as $field => $msgs) {
            $errors[$field] = array_merge($errors[$field] ?? [], $msgs);
        }

        if (!empty($errors)) {
            storeOldInput();
            flash('errors', serialize($errors));
            $this->redirect('/documents/' . $doc['id'] . '/edit');
        }

        // Handle replacement of document file (if uploaded)
        $fileService = new FileService();
        if (!empty($_FILES['document_file']['name'])) {
            $fileResult = $fileService->uploadDocument($_FILES['document_file']);
            if (!$fileResult['success']) {
                storeOldInput();
                flash('error', $fileResult['error']);
                $this->redirect('/documents/' . $doc['id'] . '/edit');
            }

            // Remove old file only when not identical to newly uploaded file and not shared with other documents
            if (!empty($doc['file_path'])) {
                if ($doc['file_path'] !== $fileResult['file_path']) {
                    if (!$this->documentModel->isFilePathInUse($doc['file_path'], (int)$doc['id'])) {
                        $fileService->deleteFile($doc['file_path']);
                    }
                } elseif (!$this->documentModel->isFilePathInUse($doc['file_path'], (int)$doc['id'], 'approved')) {
                    // Identical file re-uploaded into pending/; only clean approved/ copy if unused
                    $fileService->deleteFile($doc['file_path'], 'approved');
                }
            }

            // Update file metadata
            $data['file_path']         = $fileResult['file_path'];
            $data['original_filename'] = $fileResult['original_filename'];
            $data['mime_type']         = $fileResult['mime_type'];
            $data['file_size']         = $fileResult['file_size'];
            $data['checksum']          = $fileResult['checksum'];
        }

        $autoApprove = isModerator();
        $status      = $autoApprove ? 'approved' : 'pending';
        $approvedBy  = $autoApprove ? userId() : null;
        $approvedAt  = $autoApprove ? date('Y-m-d H:i:s') : null;

        if ($autoApprove) {
            $currentFilePath = $data['file_path'] ?? $doc['file_path'];
            if (!empty($currentFilePath)) {
                $fileService->moveToApproved($currentFilePath);
            }
        } else {
            // Re-submit for review: move existing file back to pending storage if previously approved
            if (empty($_FILES['document_file']['name']) && !empty($doc['file_path'])) {
                $keepApproved = $this->documentModel->isFilePathInUse($doc['file_path'], (int)$doc['id'], 'approved');
                $fileService->moveToPending($doc['file_path'], $keepApproved);
            }
        }

        $this->documentModel->update($doc['id'], array_merge($data, [
            'slug'             => slug($data['title']) . '-' . $doc['id'],
            'status'           => $status,
            'rejection_reason' => null,
            'approved_by'      => $approvedBy,
            'approved_at'      => $approvedAt,
        ]));

        clearOldInput();
        if ($autoApprove) {
            flash('success', 'Tài liệu đã được cập nhật thành công!');
            $this->redirect('/documents/' . $doc['id']);
        } else {
            flash('success', 'Tài liệu đã được cập nhật và gửi lại để kiểm duyệt.');
            $this->redirect('/my-documents');
        }
    }

    public function destroy(array $params = []): void
    {
        $this->requireLogin();
        $this->csrfValidate();

        $doc = $this->documentModel->findById((int)$params['id']);
        if (!$doc || !empty($doc['deleted_at'])) {
            flash('error', 'Tài liệu không tồn tại.');
            $this->redirect('/my-documents');
        }
        if (userId() !== (int)$doc['user_id'] && !isAdmin()) {
            http_response_code(403);
            die('Từ chối truy cập.');
        }
        if (!isAdmin() && ($doc['status'] ?? '') !== 'pending') {
            flash('error', 'Tài liệu đã qua kiểm duyệt, thành viên không thể chỉnh sửa.');
            $this->redirect('/my-documents');
        }

        $this->documentModel->softDelete($doc['id']);
        flash('success', 'Tài liệu đã được xóa.');
        $this->redirect('/my-documents');
    }
}

