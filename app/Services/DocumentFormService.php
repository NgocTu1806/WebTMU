<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Validator;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Subject;

class DocumentFormService
{
    /**
     * Collect and validate shared document metadata fields from POST.
     *
     * @return array{0: array, 1: array}
     */
    public function extractAndValidateInput(array $post): array
    {
        $data = [
            'title'            => trim((string)($post['title'] ?? '')),
            'description'      => trim((string)($post['description'] ?? '')),
            'subject_id'       => (int)($post['subject_id'] ?? 0),
            'document_type_id' => (int)($post['document_type_id'] ?? 0),
        ];

        $errors = (new Validator($data, [
            'title'       => 'required|min:5|max:500',
            'description' => 'max:3000',
        ]))->validate();

        if ($data['subject_id'] <= 0 || !(new Subject())->findById($data['subject_id'])) {
            $errors['subject_id'][] = 'Vui lòng chọn môn học hợp lệ.';
        }

        if ($data['document_type_id'] <= 0 || !(new DocumentType())->findById($data['document_type_id'])) {
            $errors['document_type_id'][] = 'Vui lòng chọn loại tài liệu hợp lệ.';
        }

        return [$data, $errors];
    }

    /**
     * Check duplicate title within the same subject and duplicate file content (SHA-256 checksum).
     */
    public function checkDuplicateErrors(Document $docModel, array $data, ?array $file, int $excludeId = 0): array
    {
        $errors = [];

        if (!empty($data['title']) && (int)($data['subject_id'] ?? 0) > 0) {
            $dupTitle = $docModel->findDuplicateTitleInSubject(
                (string)$data['title'],
                (int)$data['subject_id'],
                $excludeId
            );
            if ($dupTitle) {
                $errors['title'][] = 'Đã tồn tại tài liệu "' . $dupTitle['title'] . '" trong môn học này. Vui lòng không đăng trùng tài liệu.';
            }
        }

        if ($file && !empty($file['tmp_name']) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK && is_file($file['tmp_name'])) {
            $checksum = hash_file('sha256', $file['tmp_name']);
            if ($checksum) {
                $dupFile = $docModel->findByChecksum($checksum, $excludeId);
                if ($dupFile) {
                    $errors['document_file'][] = 'File tài liệu này đã tồn tại trên hệ thống (trùng nội dung với: "' . $dupFile['title'] . '").';
                }
            }
        }

        return $errors;
    }
}
