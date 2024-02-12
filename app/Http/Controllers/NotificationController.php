<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get the authenticated user's notifications.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return response()->json([
            'notifications' => auth()->user()->notifications,
            'unread_count' => auth()->user()->unreadNotifications->count()
        ]);
    }

    /**
     * Mark the authenticated user's notification as read.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($id)
    {
        auth()->user()->unreadNotifications()->where('id', $id)->first()->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read.'
        ]);
    }

    /**
     * Mark the authenticated user's notification as unread.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsUnread($id)
    {
        auth()->user()->notifications()->where('id', $id)->first()->markAsUnread();

        return response()->json([
            'message' => 'Notification marked as unread.'
        ]);
    }

    /**
     * Mark all the authenticated user's notifications as read.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read.'
        ]);
    }

    /**
     * Delete the authenticated user's notification.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($id)
    {
        auth()->user()->notifications()->where('id', $id)->delete();

        return response()->json([
            'message' => 'Notification deleted.'
        ]);
    }

    /**
     * Delete all the authenticated user's notifications.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteAll()
    {
        auth()->user()->notifications()->delete();

        return response()->json([
            'message' => 'All notifications deleted.'
        ]);
    }
}
