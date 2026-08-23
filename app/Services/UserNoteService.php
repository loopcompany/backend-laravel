<?php

namespace App\Services;

use App\Repositories\UserNoteRepository;

class UserNoteService
{
    public function __construct(
        private UserNoteRepository $repository
    ) {}

    /**
     * ایجاد یادداشت جدید
     *
     * @param array $data
     * @param int $userId
     * @return array
     */
    public function createNote(array $data, int $userId): array
    {
        $data['user_id'] = $userId;

        $note = $this->repository->create($data);

        if (!$note) {
            return [
                'success' => false,
                'message' => 'خطا در ایجاد یادداشت',
                'error_code' => 'CREATION_FAILED'
            ];
        }

        return [
            'success' => true,
            'message' => 'یادداشت با موفقیت ثبت شد',
            'data' => $note
        ];
    }

    /**
     * دریافت لیست یادداشت‌های کاربر
     *
     * @param int $userId
     * @return array
     */
    public function getUserNotes(int $userId): array
    {
        $notes = $this->repository->getUserNotes($userId);

        return [
            'success' => true,
            'message' => 'لیست یادداشت‌ها با موفقیت دریافت شد',
            'data' => $notes
        ];
    }

    /**
     * دریافت جزئیات یک یادداشت
     *
     * @param int $id
     * @param int $userId
     * @return array
     */
    public function getNoteDetail(int $id, int $userId): array
    {
        $note = $this->repository->findByIdAndUser($id, $userId);

        if (!$note) {
            return [
                'success' => false,
                'message' => 'یادداشت یافت نشد',
                'error_code' => 'NOT_FOUND'
            ];
        }

        return [
            'success' => true,
            'message' => 'جزئیات یادداشت با موفقیت دریافت شد',
            'data' => $note
        ];
    }

    /**
     * بروزرسانی یادداشت
     *
     * @param int $id
     * @param array $data
     * @param int $userId
     * @return array
     */
    public function updateNote(int $id, array $data, int $userId): array
    {
        $note = $this->repository->findByIdAndUser($id, $userId);

        if (!$note) {
            return [
                'success' => false,
                'message' => 'یادداشت یافت نشد',
                'error_code' => 'NOT_FOUND'
            ];
        }

        $updated = $this->repository->update($note, $data);

        if (!$updated) {
            return [
                'success' => false,
                'message' => 'خطا در بروزرسانی یادداشت',
                'error_code' => 'UPDATE_FAILED'
            ];
        }

        // Refresh model to get updated data
        $note->refresh();

        return [
            'success' => true,
            'message' => 'یادداشت با موفقیت بروزرسانی شد',
            'data' => $note
        ];
    }

    /**
     * حذف یادداشت
     *
     * @param int $id
     * @param int $userId
     * @return array
     */
    public function deleteNote(int $id, int $userId): array
    {
        $note = $this->repository->findByIdAndUser($id, $userId);

        if (!$note) {
            return [
                'success' => false,
                'message' => 'یادداشت یافت نشد',
                'error_code' => 'NOT_FOUND'
            ];
        }

        $deleted = $this->repository->delete($note);

        if (!$deleted) {
            return [
                'success' => false,
                'message' => 'خطا در حذف یادداشت',
                'error_code' => 'DELETE_FAILED'
            ];
        }

        return [
            'success' => true,
            'message' => 'یادداشت با موفقیت حذف شد'
        ];
    }
}
