<?php

namespace App\Http\Controllers;

use App\Models\MockBoard;
use Illuminate\Http\Request;

class MockBoardApprovalController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! in_array(auth()->user()->role, ['admin', 'superadmin'], true)) {
                abort(403, 'Admins only.');
            }

            return $next($request);
        });
    }

    /**
     * Admin: listahan ng mock boards na naghihintay ng approval (o filtered by status).
     */
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $query = MockBoard::with(['teacher', 'phases.module.quizQuestions'])
            ->orderBy('created_at', 'desc');

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }

        $mockBoards = $query->get();

        return view('pages.admin.mock-boards.approvals', [
            'mockBoards' => $mockBoards,
            'selectedStatus' => $status,
        ]);
    }

    /**
     * Admin: i-approve ang mock board.
     */
    public function approve(MockBoard $mockBoard)
    {
        $mockBoard->markApproved(auth()->user());

        return redirect()->back()->with('success', "\"{$mockBoard->title}\" has been approved and is now visible to students.");
    }

    /**
     * Admin: i-reject ang mock board, na may optional na dahilan.
     */
    public function reject(Request $request, MockBoard $mockBoard)
    {
        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $mockBoard->markRejected(auth()->user(), $validated['rejection_reason'] ?? null);

        return redirect()->back()->with('success', "\"{$mockBoard->title}\" has been rejected.");
    }
}
