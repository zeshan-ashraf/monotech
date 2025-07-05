<?php

namespace App\Http\Controllers\Admin\Authorization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        $users = User::whereHas('roles')->with('roles')->orderBy('user_role', 'desc')->get();
        return view('admin.authorization.teams.teams', get_defined_vars());
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {

        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'role' => 'required'
        ]);

        $random_password = Str::random(10);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => 'admin',
            'visible_password' => $request->password,
            'password' => Hash::make($request->password)
        ]);
        $user->assignRole($request->role);


        // sendMail([
        //         'view' => 'emails.admin.team',
        //         'to' => $user->email,
        //         'subject' => 'You have been Assigned a role at Nova Connect',
        //         'data' => [
        //             'name'=> $user->name,
        //             'email'=> $user->email,
        //             'password'=> $random_password,
        //         ]
        // ]);
        return redirect()->route('admin.teams.index')->with('message','Team Member has been created.');

    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function remove($id)
    {
        User::find($id)->delete();
        return back()->with('message','Team Member has been deleted.');
    }
    public function active(Request $request)
    {
        User::where('id',$request->id)->update([$request->type => $request->status]);

        return redirect()->back();
    }
}
