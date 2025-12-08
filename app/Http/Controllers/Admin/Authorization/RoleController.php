<?php

namespace App\Http\Controllers\Admin\Authorization;

use DB;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{

    public function index()
    {
        
        $roles = Role::with('permissions')->get();
        $permissions = Permission::get();
        
        return view('admin.authorization.roles.roles', get_defined_vars());
    }

    public function create()
    {
        //
    }


    public function store(Request $request)
    {


        $this->validate($request, [
            'name' => 'required|unique:roles,name',
        ]);

        $role = Role::create(['name' => $request->name]);
        $role->syncPermissions($request->permissions);

        return back()->with('message', 'New role has been created.');


    }


    public function show($id)
    {
        //
    }

    public function edit($id)
    {


        $role = Role::where('id', $id)->with('permissions')->first();
        $permissions = Permission::get();
        return view('admin.authorization.roles.edit_roles', get_defined_vars());

    }


    public function update(Request $request, $id)
    {

        // dd($request->all());

        $role = Role::find($id);
        $role->name = $request->name;
        $role->save();
        $role->syncPermissions($request->permissions);
        return redirect()->route('admin.roles.index')->with('message', $request->name.' role has been updated.');
    }


    public function destroy($id)
    {
        //
    }

    public function delete($id)
    {
        Role::findOrFail($id)->delete();
        return back()->withMessage('Role has been deleted.');
    }

    public function addPermission($permission)
    {
        Permission::create(['name' => $permission]);
        dd('Permission has been created.');
    }

}
