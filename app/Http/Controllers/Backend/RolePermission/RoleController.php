<?php

namespace App\Http\Controllers\Backend\RolePermission;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    // ── Index ───────────────────────────────────────────────────────────────────

    public function index()
    {
        abort_if(!auth()->user()->can('role_view'), 403);

        $roles       = Role::withCount('permissions')->get();
        $permissions = Permission::all();

        return view('backend.settings.role.index', compact('roles', 'permissions'));
    }

    // ── Store ───────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        abort_if(!auth()->user()->can('role_create'), 403);

        $request->validate(['name' => 'required|unique:roles']);

        Role::create($request->only('name'));

        return back()->with('success', 'Role "' . $request->name . '" created successfully');
    }

    // ── Update ──────────────────────────────────────────────────────────────────

    public function update(Request $request, $id)
    {
        abort_if(!auth()->user()->can('role_update'), 403); // Fixed: was currency_update

        $request->validate(['name' => "required|unique:roles,name,{$id}"]);

        $role = Role::findOrFail($id);
        $role->update(['name' => $request->name]);

        return back()->with('success', 'Role "' . $role->name . '" updated successfully');
    }

    // ── Show permissions ────────────────────────────────────────────────────────

    public function show($id)
    {
        abort_if(!auth()->user()->can('role_view'), 403);

        $role        = Role::findOrFail($id);
        $permissions = Permission::all();

        // Group permissions by module prefix (e.g. product_*, order_*)
        $grouped = $permissions->groupBy(function ($p) {
            $parts = explode('_', $p->name);
            return $parts[0]; // e.g. "product", "order"
        })->sortKeys();

        return view('backend.settings.role.permissions', compact('role', 'grouped'));
    }

    // ── Destroy ─────────────────────────────────────────────────────────────────

    public function destroy($id)
    {
        abort_if(!auth()->user()->can('role_delete'), 403);

        $role = Role::findOrFail($id);

        // Guard the Admin role by name, not magic ID
        if (strtolower($role->name) === 'admin') {
            return back()->with('error', 'The Admin role cannot be deleted.');
        }

        $role->delete();

        return back()->with('success', 'Role "' . $role->name . '" deleted successfully');
    }

    // ── Update permissions ──────────────────────────────────────────────────────

    public function updatePermission(Request $request, $id)
    {
        abort_if(!auth()->user()->can('role_update'), 403);

        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::findOrFail($id);

        // Admin always gets everything
        if (strtolower($role->name) === 'admin') {
            $role->syncPermissions(Permission::all());
            return to_route('backend.admin.roles')
                ->with('warning', 'Admin role always has all permissions.');
        }

        $permissions = $request->get('permissions', []);
        $role->syncPermissions($permissions);

        return back()->with('success', ucfirst($role->name) . ' permissions updated successfully');
    }

    // ── API: role-wise permissions (JSON) ───────────────────────────────────────

    public function roleWisePermissions($id)
    {
        $data = Role::findOrFail($id);
        return response()->json($data->permissions, 200);
    }
}
