<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\SetupPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UsefulLInkController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = "Useful Links";
        $useful_links = SetupPage::where('type','useful-links')->get();
        return view('admin.sections.useful-links.index',compact(
            'page_title',
            'useful_links',
        ));
    } 
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $page_title = "Useful Link Edit";
        $useful_link = SetupPage::findOrFail($id);
        return view('admin.sections.useful-links.edit',compact(
            'page_title', 
            'useful_link',
        ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'target'        => 'required|string',
            'title'         => 'required|string|max:100',
            'details'       => 'required|string',
        ]);

        if($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('modal','useful-link-edit');
        }

        $validated = $validator->validated();
        $useful_link = SetupPage::findOrFail($validated['target']);

        try {
            $useful_link->update($validated);
        } catch (\Throwable $th) {
            return back()->with(['error' => ['Something went worng! Please try again']]);
        }

        return redirect()->route('admin.useful.links.index')->with(['success' => ['Page updated successfully!']]);
    } 
}
