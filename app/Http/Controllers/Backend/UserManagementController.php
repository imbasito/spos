<?php

namespace App\Http\Controllers\Backend;

use App\Models\User;
use Illuminate\Http\Request;
use App\Rules\ValidImageType;
use Yajra\DataTables\DataTables;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Trait\FileHandler;

class UserManagementController extends Controller
{
    protected $fileHandler;

    public function __construct(FileHandler $fileHandler)
    {
        $this->fileHandler = $fileHandler;
    }

    // ── Index (DataTable) ────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        abort_if(!auth()->user()->can('user_view'), 403);

        if ($request->ajax()) {
            // Memory protection: cap DataTable length
            if ($request->has('length') && $request->length > 500) {
                $request->merge(['length' => 500]);
            }

            $users = User::with('roles')->select('users.*');

            return DataTables::of($users)
                ->addIndexColumn()
                ->filter(function ($query) {
                    if (request()->has('search.value')) {
                        $keyword = request('search.value');
                        if (!empty($keyword)) {
                            $query->where(function ($q) use ($keyword) {
                                $q->where('name', 'like', "%{$keyword}%")
                                  ->orWhere('email', 'like', "%{$keyword}%");
                            });
                        }
                    }
                })
                ->addColumn('thumb', function ($data) {
                    $url = $data->profile_image
                        ? asset('storage/' . $data->profile_image)
                        : asset('assets/images/no-image.png');
                    return '<img class="img-circle shadow-sm" src="' . $url . '" width="40" height="40" style="object-fit:cover;border:2px solid #fff;">';
                })
                ->addColumn('created', function ($data) {
                    return date('d M, Y', strtotime($data->created_at));
                })
                ->addColumn('roles', function ($data) {
                    // Fixed: was returning inside foreach causing dead code after return
                    return $data->roles->pluck('name')->implode(', ') ?: '<span class="text-muted">—</span>';
                })
                ->addColumn('suspend', function ($data) {
                    return $data->is_suspended == 0
                        ? '<span class="badge badge-pill badge-success">Active</span>'
                        : '<span class="badge badge-pill badge-danger">Suspended</span>';
                })
                ->addColumn('action', function ($data) {
                    $editBtn = '<a class="btn btn-sm bg-gradient-primary mr-1"
                        href="' . route('backend.admin.user.edit', $data->id) . '">
                        <i class="fas fa-edit"></i> Edit
                    </a>';

                    $deleteBtn = '';
                    $suspendBtn = '';

                    // Guard: cannot delete self or the seeded admin (ID=1)
                    if ($data->id !== auth()->id() && $data->id !== 1) {
                        $deleteBtn = '<form action="' . route('backend.admin.user.delete', $data->id) . '"
                            method="POST" class="d-inline"
                            onsubmit="return confirm(\'Delete user ' . e($data->name) . '? This cannot be undone.\')">
                            <input type="hidden" name="_token" value="' . csrf_token() . '">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="btn btn-sm bg-gradient-danger">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                        </form>';
                    }

                    if ($data->is_suspended == 0) {
                        $suspendBtn = '<a class="btn btn-sm bg-gradient-warning ml-1"
                            href="' . route('backend.admin.user.suspend', ['id' => $data->id, 'status' => 1]) . '"
                            onclick="return confirm(\'Suspend this user?\')">
                            <i class="far fa-times-circle"></i> Suspend
                        </a>';
                    } else {
                        $suspendBtn = '<a class="btn btn-sm bg-gradient-success ml-1"
                            href="' . route('backend.admin.user.suspend', ['id' => $data->id, 'status' => 0]) . '">
                            <i class="fas fa-check-square"></i> Activate
                        </a>';
                    }

                    return '<div class="d-flex flex-wrap gap-1">' . $editBtn . $deleteBtn . $suspendBtn . '</div>';
                })
                ->rawColumns(['thumb', 'created', 'action', 'suspend', 'roles'])
                ->toJson();
        }

        return view('backend.users.index');
    }

    // ── AJAX data endpoint (kept for legacy compatibility) ───────────────────────

    public function fetchPageData(Request $request)
    {
        return $this->index($request);
    }

    // ── Suspend / Activate ───────────────────────────────────────────────────────

    public function suspend($id, $status)
    {
        abort_if(!auth()->user()->can('user_suspend'), 403);

        $user = User::findOrFail($id);

        if ($user->is_suspended == $status) {
            return back()->with('error', 'User status is already set to that state.');
        }

        $user->is_suspended = $status;
        $user->save();

        return back()->with('success', 'User "' . $user->name . '" ' . ($status ? 'suspended' : 'activated') . ' successfully.');
    }

    // ── Create ───────────────────────────────────────────────────────────────────

    public function create(Request $request)
    {
        abort_if(!auth()->user()->can('user_create'), 403);

        if ($request->isMethod('post')) {
            $request->validate([
                'name'          => 'required',
                'email'         => 'required|email|unique:users,email',
                'role'          => 'required',
                'password'      => 'required|min:6',
                'profile_image' => ['nullable', 'file', new ValidImageType],
            ]);

            $user           = new User();
            $user->name     = $request->name;
            $user->email    = $request->email;
            $user->password = bcrypt($request->password);
            $user->username = uniqid();

            if ($request->hasFile('profile_image')) {
                $user->profile_image = $this->fileHandler->fileUploadAndGetPath(
                    $request->file('profile_image'), '/public/media/users'
                );
            }

            $user->save();
            $user->syncRoles(Role::findOrFail($request->role));

            return to_route('backend.admin.users')->with('success', 'User "' . $user->name . '" created successfully.');
        }

        $roles = Role::all();
        return view('backend.users.create', compact('roles'));
    }

    // ── Edit ─────────────────────────────────────────────────────────────────────

    public function edit(Request $request, $id)
    {
        abort_if(!auth()->user()->can('user_update'), 403);

        $user = User::with('roles')->findOrFail($id);

        // Redirect to own profile page when editing self
        if ($request->isMethod('get') && $id == auth()->id()) {
            return to_route('backend.admin.profile');
        }

        if ($request->isMethod('post')) {
            $request->validate([
                'name'          => 'required',
                'email'         => 'required|email|unique:users,email,' . $id,
                'role'          => 'required',
                'password'      => 'nullable|min:6', // Optional on edit
                'profile_image' => ['nullable', 'file', new ValidImageType],
            ]);

            $user->name = $request->name;

            if ($request->email !== $user->email) {
                $user->email                = $request->email;
                $user->google_id            = null;
                $user->is_google_registered = false;
            }

            // Only update password if a new one is provided
            if ($request->filled('password')) {
                $user->password = bcrypt($request->password);
            }

            if ($request->hasFile('profile_image')) {
                $this->fileHandler->secureUnlink($user->profile_image);
                $user->profile_image = $this->fileHandler->fileUploadAndGetPath(
                    $request->file('profile_image'), '/public/media/users'
                );
            }

            $user->save();
            $user->syncRoles(Role::findOrFail($request->role));

            return to_route('backend.admin.users')->with('success', 'User "' . $user->name . '" updated successfully.');
        }

        $roles = Role::all();
        return view('backend.users.edit', compact('user', 'roles'));
    }

    // ── Delete ───────────────────────────────────────────────────────────────────

    public function delete($id)
    {
        abort_if(!auth()->user()->can('user_delete'), 403);

        if ($id == auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        if ($id == 1) {
            return back()->with('error', 'The master admin account cannot be deleted.');
        }

        $user = User::findOrFail($id);
        $name = $user->name;
        $user->delete();

        return back()->with('success', 'User "' . $name . '" deleted successfully.');
    }
}
