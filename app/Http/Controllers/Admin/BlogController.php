<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Http\Helpers\Response;
use App\Models\Blog;
use App\Models\BlogCategory; 
use App\Models\Admin\SiteSections;
use App\Models\Admin\Language;
use Illuminate\Support\Facades\Auth;
use App\Constants\SiteSectionConst;
use App\Constants\LanguageConst;

class BlogController extends Controller
{
    protected $languages; 
    public function __construct()
    {
        $this->languages = Language::whereNot('code',LanguageConst::NOT_REMOVABLE)->get();
    }
    /**
     * Method for get languages form record with little modification for using only this class
     * @return array $languages
     */
    public function languages() {
        $languages = Language::whereNot('code',LanguageConst::NOT_REMOVABLE)->select("code","name")->get()->toArray();
        $languages[] = [
            'name'      => LanguageConst::NOT_REMOVABLE_CODE,
            'code'      => LanguageConst::NOT_REMOVABLE,
        ];
        return $languages;
    }
   /**
     * Method for validate request data and re-decorate language wise data
     * @param object $request
     * @param array $basic_field_name
     * @return array $language_wise_data
     */
    public function contentValidate($request,$basic_field_name,$modal = null) {
        $languages = $this->languages();

        $current_local = get_default_language_code();
        $validation_rules = [];
        $language_wise_data = [];
        foreach($request->all() as $input_name => $input_value) {
            foreach($languages as $language) {
                $input_name_check = explode("_",$input_name);
                $input_lang_code = array_shift($input_name_check);
                $input_name_check = implode("_",$input_name_check);
                if($input_lang_code == $language['code']) {
                    if(array_key_exists($input_name_check,$basic_field_name)) {
                        $langCode = $language['code'];
                        if($current_local == $langCode) {
                            $validation_rules[$input_name] = $basic_field_name[$input_name_check];
                        }else {
                            $validation_rules[$input_name] = str_replace("required","nullable",$basic_field_name[$input_name_check]);
                        }
                        $language_wise_data[$langCode][$input_name_check] = $input_value;
                    }
                    break;
                }
            }
        }
        if($modal == null) {
            $validated = Validator::make($request->all(),$validation_rules)->validate();
        }else {
            $validator = Validator::make($request->all(),$validation_rules);
            if($validator->fails()) {
                return back()->withErrors($validator)->withInput()->with("modal",$modal);
            }
            $validated = $validator->validate();
        } 
        return $language_wise_data;
    }

    /**
     * Method for validate request image if have
     * @param object $request
     * @param string $input_name
     * @param string $old_image
     * @return boolean|string $upload
     */
    public function imageValidate($request,$input_name,$old_image) {
        if($request->hasFile($input_name)) {
            $image_validated = Validator::make($request->only($input_name),[
                $input_name         => "image|mimes:png,jpg,webp,jpeg,svg",
            ])->validate();

            $image = get_files_from_fileholder($request,$input_name);
            $upload = upload_files_from_path_dynamic($image,'site-section',$old_image);
            return $upload;
        } 
        return false;
    }
    //=======================Category  Section Start=======================
    public function categoryView(){
        $page_title = "Setup Blog Category";
        $allCategory = BlogCategory::orderByDesc('id')->paginate(10);
        return view('admin.sections.blog-category.index',compact(
            'page_title',
            'allCategory',
        ));
    }
    public function storeCategory(Request $request){

        $validator = Validator::make($request->all(),[
            'name'      => 'required|string|max:200|unique:blog_categories,name',
        ]);
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('modal','category-add');
        }
        $validated = $validator->validate();
        $slugData = Str::slug($request->name);
        $makeUnique = BlogCategory::where('slug',  $slugData)->first();
        if($makeUnique){
            return back()->with(['error' => [ $request->name.' '.'Category Already Exists!']]);
        }
        $admin = Auth::user();
        
        $validated['admin_id']      = $admin->id;
        $validated['name']          = $request->name;
        $validated['slug']          = $slugData; 
        try{
            BlogCategory::create($validated);
            return back()->with(['success' => ['Category Saved Successfully!']]);
        }catch(Exception $e) { 
            return back()->withErrors($validator)->withInput()->with(['error' => ['Something went worng! Please try again.']]);
        }
    }
    public function categoryUpdate(Request $request){
        $target = $request->target;
        $category = BlogCategory::where('id',$target)->first();
        $validator = Validator::make($request->all(),[
            'name'      => 'required|string|max:200',
        ]);
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('modal','edit-category');
        }
        $validated = $validator->validate();

        $slugData = Str::slug($request->name);
        $makeUnique = BlogCategory::where('id',"!=",$category->id)->where('slug',  $slugData)->first();
        if($makeUnique){
            return back()->with(['error' => [ $request->name.' '.'Category Already Exists!']]);
        }
        $admin = Auth::user();
        $validated['admin_id']      = $admin->id;
        $validated['name']          = $request->name;
        $validated['slug']          = $slugData;

        try{
            $category->fill($validated)->save();
            return back()->with(['success' => ['Category Updated Successfully!']]);
        }catch(Exception $e) {
            return back()->withErrors($validator)->withInput()->with(['error' => ['Something went worng! Please try again.']]);
        }
    }
    public function categoryStatusUpdate(Request $request) {
        $validator = Validator::make($request->all(),[
            'status'                    => 'required|boolean',
            'data_target'               => 'required|string',
        ]);
        if ($validator->stopOnFirstFailure()->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error,null,400);
        }
        $validated = $validator->safe()->all();
        $category_id = $validated['data_target'];

        $category = BlogCategory::where('id',$category_id)->first();
        if(!$category) {
            $error = ['error' => ['Category record not found in our system.']];
            return Response::error($error,null,404);
        }

        try{
            $category->update([
                'status' => ($validated['status'] == true) ? false : true,
            ]);
        }catch(Exception $e) {
            $error = ['error' => ['Something went worng!. Please try again.']];
            return Response::error($error,null,500);
        }

        $success = ['success' => ['Category status updated successfully!']];
        return Response::success($success,null,200);
    }
    public function categoryDelete(Request $request) {
        $validator = Validator::make($request->all(),[
            'target'        => 'required|string|exists:blog_categories,id',
        ]);
        $validated = $validator->validate();
        $category = BlogCategory::where("id",$validated['target'])->first();

        try{
            $category->delete();
        }catch(Exception $e) {
            return back()->with(['error' => ['Something went worng! Please try again.']]);
        }

        return back()->with(['success' => ['Category deleted successfully!']]);
    }
    public function categorySearch(Request $request) {
        $validator = Validator::make($request->all(),[
            'text'  => 'required|string',
        ]);

        if($validator->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error,null,400);
        }

        $validated = $validator->validate();

        $allCategory = BlogCategory::search($validated['text'])->select()->limit(10)->get();
        return view('admin.components.search.category-search',compact(
            'allCategory',
        ));
    }
    //=======================Category  Section End=======================
    //=======================================blog section Start =====================================
    public function blogView() {
        $page_title = "Blog Section";
        $section_slug = Str::slug(SiteSectionConst::BLOG_SECTION);
        $data = SiteSections::getData($section_slug)->first();
        $languages = $this->languages;
        $categories = BlogCategory::where('status',1)->latest()->get();
        $blogs = Blog::latest()->paginate(10);

        return view('admin.sections.setup-sections.blog-section',compact(
            'page_title',
            'data',
            'languages', 
            'categories',
            'blogs'
        ));
    } 
    public function blogItemStore(Request $request){
        $validator = Validator::make($request->all(),[
            'category_id'      => 'required|integer',
            'en_name'     => "required|string",
            'en_details'     => "required|string",
            'tags'          => 'nullable|array',
            'tags.*'        => 'nullable|string|max:30',
            'image'         => 'nullable|image|mimes:png,jpg,jpeg,svg,webp',
        ]); 
        $name_filed = [
            'name'     => "required|string",
        ];
        $details_filed = [
            'details'     => "required|string",
        ];

        if($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('modal','blog-add');
        }
        $validated = $validator->validate();

        // Multiple language data set 
        $language_wise_name = $this->contentValidate($request,$name_filed);
        $language_wise_details = $this->contentValidate($request,$details_filed);
        
        $name_data['language'] = $language_wise_name;
        $details_data['language'] = $language_wise_details;

        $validated['category_id']        = $request->category_id;
        $validated['admin_id']        = Auth::user()->id;
        $validated['name']            = $name_data;
        $validated['details']           = $details_data;
        $validated['slug']            = Str::slug($name_data['language']['en']['name']);
        $validated['tag']           = $request->tags;
        $validated['created_at']      = now();
 
        // Check Image File is Available or not
        if($request->hasFile('image')) {
            $image = get_files_from_fileholder($request,'image');
            $upload = upload_files_from_path_dynamic($image,'blog');
            $validated['image'] = $upload;
        } 
        try{
            Blog::create($validated);
        }catch(Exception $e) {
            return back()->with(['error' => ['Something went worng! Please try again']]);
        } 
        return back()->with(['success' => ['Blog item added successfully!']]); 
    }
    public function blogEdit($id)
    {
        $page_title = "Blog Edit";
        $languages = $this->languages;
        $data = Blog::findOrFail($id);
        $categories = BlogCategory::where('status',1)->latest()->get();

        return view('admin.sections.setup-sections.blog-section-edit', compact(
            'page_title',
            'languages',
            'data',
            'categories',
        ));
    }
    public function blogItemUpdate(Request $request) { 
        $validator = Validator::make($request->all(),[
            'category_id'      => 'required|integer',
            'en_name'     => "required|string",
            'en_details'     => "required|string",
            'tags'          => 'nullable|array',
            'tags.*'        => 'nullable|string|max:30',
            'image'         => 'nullable|image|mimes:png,jpg,jpeg,svg,webp',
            'target'        => 'required|integer',
        ]);

        $short_title_field = [
            'short_title'     => "nullable|string"
        ];
        $name_filed = [
            'name'     => "required|string",
        ];
        $details_filed = [
            'details'     => "required|string",
        ];

        if($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('modal','blog-edit');
        }
        $validated = $validator->validate();
        $blog = Blog::findOrFail($validated['target']);

        // Multiple language data set
        $language_wise_stitle = $this->contentValidate($request,$short_title_field);
        $language_wise_name = $this->contentValidate($request,$name_filed);
        $language_wise_details = $this->contentValidate($request,$details_filed);

        $short_title_data['language'] = $language_wise_stitle;
        $name_data['language'] = $language_wise_name;
        $details_data['language'] = $language_wise_details;

        $validated['category_id']        = $request->category_id;
        $validated['admin_id']        = Auth::user()->id;
        $validated['short_title']            = $short_title_data;
        $validated['name']            = $name_data;
        $validated['details']           = $details_data;
        $validated['slug']            = Str::slug($name_data['language']['en']['name']);
        $validated['tag']           = $request->tags;
        $validated['created_at']      = now();

        // Check Image File is Available or not
        if($request->hasFile('image')) {

                $image = get_files_from_fileholder($request,'image');
                $upload = upload_files_from_path_dynamic($image,'blog',$blog->image);
                $validated['image'] = $upload;

        } 
        try{
            $blog->update($validated);
        }catch(Exception $e) {
            return back()->with(['error' => ['Something went worng! Please try again']]);
        } 
        return back()->with(['success' => ['Blog item updated successfully!']]);
    } 
    public function blogItemDelete(Request $request) {
        $request->validate([
            'target'    => 'required|string',
        ]);

        $blog = Blog::findOrFail($request->target);

        try{
            $image_link = get_files_path('blog') . '/' . $blog->image;
            delete_file($image_link);
            $blog->delete();
        }catch(Exception $e) {
            return back()->with(['error' => ['Something went worng! Please try again.']]);
        }

        return back()->with(['success' => ['BLog delete successfully!']]);
    }
    public function blogStatusUpdate(Request $request) {
        $validator = Validator::make($request->all(),[
            'status'                    => 'required|boolean',
            'data_target'               => 'required|string',
        ]);
        if ($validator->stopOnFirstFailure()->fails()) {
            $error = ['error' => $validator->errors()];
            return Response::error($error,null,400);
        }
        $validated = $validator->safe()->all();
        $blog_id = $validated['data_target'];

        $blog = Blog::where('id',$blog_id)->first();
        if(!$blog) {
            $error = ['error' => ['Blog record not found in our system.']];
            return Response::error($error,null,404);
        } 
        try{
            $blog->update([
                'status' => ($validated['status'] == true) ? false : true,
            ]);
        }catch(Exception $e) {
            $error = ['error' => ['Something went worng!. Please try again.']];
            return Response::error($error,null,500);
        } 
        $success = ['success' => ['Blog status updated successfully!']];
        return Response::success($success,null,200);
    }
    //=======================================blog section End ==========================================
}
