<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Client,User};
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:Clients']);
    }
    /**
     * Display a listing of the resource.
     */
    public function list()
    {
        $list = Client::all();
        return view('admin.client.list', get_defined_vars());
    }
    /**
     * Show the form for creating a new resource.
     */
    public function modal(Request $request)
    {
        $id = $request->id;
        $item = $id == null ? null : Client::findOrFail($id);
        $html = view('admin.client.modal',get_defined_vars())->render();
        return response()->json(['html'=>$html]);
    }
    public function modalSec(Request $request)
    {
        $id = $request->id;
        $item = $id == null ? null : User::findOrFail($id);
        $html = view('admin.client.modal_sec',get_defined_vars())->render();
        return response()->json(['html'=>$html]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'=>'required',
            'url'=>'required',
        ]);    
        if(!$request->id){
            $request->validate([
                'photo'=>'required',
            ]);   
        }
        if($request->photo)
        {
            $imageName = time() . '.' . $request->photo->extension();
            $request->photo->move(public_path('uploads/clients'), $imageName);
            $path = 'uploads/clients/'.$imageName;
            $request->merge(['image'=>$path]);
        }
        if($request->id){
            $item = Client::findOrFail($request->id);
            $item->update($request->except('_token','id'));
            $msg = "Client Updated Successfully!";
        }
        else{
            Client::create($request->except('_token','id'));
            $msg = "Client Created Successfully!";
        }
        return redirect()->back()->with('message',$msg);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Client::findOrFail($id)->delete();
        return redirect()->back()->with('message','Client Deleted Successfully!');
    }
    public function userList()
    {
        $list=User::where('user_role','Client')
        ->where('active',1)
        ->get();
        return view('admin.client.user_list',get_defined_vars());
    }
    public function userStore(Request $request)
    {
        $request->validate([
            'payin_fee'=>'required',
            'payout_fee'=>'required',
            'per_payin_fee'=>'required',
            'per_payout_fee'=>'required',
        ]);    
        
        $item = User::findOrFail($request->id);
        $item->update($request->except('_token','id'));
        $msg = "Client Fee Updated Successfully!";
        
        return redirect()->back()->with('message',$msg);
    }
}