<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use App\Models\EscrowCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\Response;

class EscrowCategoryController extends Controller
{
    public function index(){
        $page_title = "Escrow Category";
        $escrowCategories = EscrowCategory::latest()->get();
        return view('admin.sections.escrow-category.index', compact('page_title','escrowCategories'));
    }
    public function store(Request $request){
        $validator = Validator::make($request->all(),[
            'name'      => 'required|string',
        ]);
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('modal','escrow_category_add');
        }
        $validated = $validator->validate();
        
        $validated['added_by']      = Auth::user()->id;
        $validated['slug']      = Str::slug($validated['name']);
        $validated['created_at']    = now();

        try{
            EscrowCategory::create($validated);
        }catch(Exception $e) {
            throw new Exception($e->getMessage());
            return back()->withErrors($validator)->withInput()->with(['error' => ['Something went wrong! Please try again.']]);
        }
        return back()->with(['success' => ['Escrow Category Saved Successfully!']]);
    }
    public function delete(Request $request) { 
        $validator = Validator::make($request->all(),[
            'target'        => 'required',
        ]);
        $validated = $validator->validate();
        $escrowCategory = EscrowCategory::find($validated['target']);
 
        try{
            $escrowCategory->delete(); 
        }catch(Exception $e) {
            return back()->with(['error' => ['Something went wrong! Please try again.']]);
        } 
        return back()->with(['success' => ['Escrow category deleted successfully!']]);
    }
    public function update(Request $request){
        $validator = Validator::make($request->all(),[
            'target'      => 'required',
            'name'      => 'required|string',
        ]);
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('modal','escrow_category_add');
        }
        $validated = $validator->validate();
        
        $escrowCategory = EscrowCategory::find($validated['target']);
        $validated['added_by']      = Auth::user()->id;
        $validated['slug']      = Str::slug($validated['name']); 

        try{
            $escrowCategory->update($validated);
        }catch(Exception $e) {
            throw new Exception($e->getMessage());
            return back()->withErrors($validator)->withInput()->with(['error' => ['Something went wrong! Please try again.']]);
        }
        return back()->with(['success' => ['Escrow Category Saved Successfully!']]);
    }
    public function search(Request $request) {
        $validator = Validator::make($request->all(),[
            'text'  => 'required|string',
        ]);

        if($validator->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error,null,400);
        }

        $validated = $validator->validate();
        $escrowCategories = EscrowCategory::search($validated['text'])->select()->limit(10)->get();
        return view('admin.components.data-table.escrow-category-table',compact(
            'escrowCategories',
        ));
    }
       /**
     * Update Category Status
     */
    public function statusUpdate(Request $request) { 
        $validator = Validator::make($request->all(),[
            'status'                    => 'required|boolean',
            'data_target'               => 'required',
        ]);
        if ($validator->stopOnFirstFailure()->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error,null,400);
        }
        $validated = $validator->safe()->all(); 

        $category = EscrowCategory::findOrFail($validated['data_target']);
        if(!$category) {
            $error = ['error' => ['Currency record not found in our system.']];
            return Response::error($error,null,404);
        } 
        try{
            $category->update([
                'status' => ($validated['status'] == true) ? false : true,
            ]);
        }catch(Exception $e) {
            $error = ['error' => ['Something went wrong!. Please try again.']];
            return Response::error($error,null,500);
        }

        $success = ['success' => ['Category status updated successfully!']];
        return Response::success($success,null,200);
    }
}
