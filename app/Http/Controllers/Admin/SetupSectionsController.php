<?php

namespace App\Http\Controllers\Admin;

use App\Constants\LanguageConst;
use App\Constants\SiteSectionConst;
use App\Http\Controllers\Controller;
use App\Models\Admin\Language;
use App\Models\Admin\SiteSections;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;

class SetupSectionsController extends Controller
{
    protected $languages;

    public function __construct()
    {
        $this->languages = Language::whereNot('code',LanguageConst::NOT_REMOVABLE)->get();
    }

    /**
     * Register Sections with their slug
     * @param string $slug
     * @param string $type
     * @return string
     */
    public function section($slug,$type) {
        $sections = [
            'auth-section'    => [
                'view'      => "authView",
                'update'    => "authUpdate",
            ],
            'banner-section'  => [
                'view'      => "bannerView",
                'update'    => "bannerUpdate",
            ],
            'brand-section'  => [
                'view'      => "brandView",
                'update'    => "brandUpdate",
                'itemStore'     => "brandItemStore",
                'itemUpdate'    => "brandItemUpdate",
                'itemDelete'    => "brandItemDelete",
            ],
            'about-section'  => [
                'view'      => "aboutView",
                'update'    => "aboutUpdate",
                'itemStore'     => "aboutItemStore",
                'itemUpdate'    => "aboutItemUpdate",
                'itemDelete'    => "aboutItemDelete",
            ],
            'service-section'  => [
                'view'      => "serviceView",
                'update'    => "serviceUpdate",
                'itemStore'     => "serviceItemStore",
                'itemUpdate'    => "serviceItemUpdate",
                'itemDelete'    => "serviceItemDelete",
            ],
            'feature-section'  => [
                'view'      => "featureView",
                'update'    => "featureUpdate",
                'itemStore'     => "featureItemStore",
                'itemUpdate'    => "featureItemUpdate",
                'itemDelete'    => "featureItemDelete",
            ],
            'contact-section'  => [
                'view'      => "contactView",
                'update'    => "contactUpdate",
                'itemStore'     => "contactItemStore",
                'itemUpdate'    => "contactItemUpdate",
                'itemDelete'    => "contactItemDelete",
            ],
            'app-section'  => [
                'view'      => "appView",
                'update'    => "appUpdate", 
                'itemStore'     => "appItemStore",
                'itemUpdate'    => "appItemUpdate",
                'itemDelete'    => "appItemDelete",
            ],
            'testimonial-section'  => [
                'view'      => "testimonialView",
                'update'    => "testimonialUpdate", 
                'itemStore'     => "testimonialItemStore",
                'itemUpdate'    => "testimonialItemUpdate",
                'itemDelete'    => "testimonialItemDelete",
            ],
            'faq-section'  => [
                'view'      => "faqView",
                'update'    => "faqUpdate",
                'itemStore'     => "faqItemStore",
                'itemUpdate'    => "faqItemUpdate",
                'itemDelete'    => "faqItemDelete",
            ],
        ];

        if(!array_key_exists($slug,$sections)) abort(404);
        if(!isset($sections[$slug][$type])) abort(404);
        $next_step = $sections[$slug][$type];
        return $next_step;
    }

    /**
     * Method for getting specific step based on incomming request
     * @param string $slug
     * @return method
     */
    public function sectionView($slug) {
        $section = $this->section($slug,'view');
        return $this->$section($slug);
    }

    /**
     * Method for distribute store method for any section by using slug
     * @param string $slug
     * @param \Illuminate\Http\Request  $request
     * @return method
     */
    public function sectionItemStore(Request $request, $slug) {
        $section = $this->section($slug,'itemStore');
        return $this->$section($request,$slug);
    }

    /**
     * Method for distribute update method for any section by using slug
     * @param string $slug
     * @param \Illuminate\Http\Request  $request
     * @return method
     */
    public function sectionItemUpdate(Request $request, $slug) {
        $section = $this->section($slug,'itemUpdate');
        return $this->$section($request,$slug);
    }

    /**
     * Method for distribute delete method for any section by using slug
     * @param string $slug
     * @param \Illuminate\Http\Request  $request
     * @return method
     */
    public function sectionItemDelete(Request $request,$slug) {
        $section = $this->section($slug,'itemDelete');
        return $this->$section($request,$slug);
    }

    /**
     * Method for distribute update method for any section by using slug
     * @param string $slug
     * @param \Illuminate\Http\Request  $request
     * @return method
     */
    public function sectionUpdate(Request $request,$slug) {
        $section = $this->section($slug,'update');
        return $this->$section($request,$slug);
    }

    /**
     * Mehtod for show banner section page
     * @param string $slug
     * @return view
     */
    //=======================================Auth section Start =======================================
    public function authView($slug) {
        $page_title = "Auth Section";
        $section_slug = Str::slug(SiteSectionConst::AUTH_SECTION);
        $data = SiteSections::getData($section_slug)->first();
        $languages = $this->languages;

        return view('admin.sections.setup-sections.auth-section',compact(
            'page_title',
            'data',
            'languages',
            'slug',
        ));
    }
    public function authUpdate(Request $request,$slug) {
        $basic_field_name = [
            'login_title' => "required|string",
            'login_text' => "required|string",
            'register_title' => "required|string",
            'register_text' => "required|string",
            'forget_title' => "required|string",
            'forget_text' => "required|string",
        ];
        $slug = Str::slug(SiteSectionConst::AUTH_SECTION);
        $section = SiteSections::where("key",$slug)->first();
        $data['language']  = $this->contentValidate($request,$basic_field_name);
        $update_data['value']  = $data;
        $update_data['key']    = $slug;
        try{
            SiteSections::updateOrCreate(['key' => $slug],$update_data);
        }catch(Exception $e) {
            return back()->with(['error' => ['Something went worng! Please try again.']]);
        }
        return back()->with(['success' => ['Section updated successfully!']]);
    }

//=======================================Auth section End ==========================================
    public function bannerView($slug) {
        $page_title = "Banner Section";
        $section_slug = Str::slug(SiteSectionConst::BANNER_SECTION);
        $data = SiteSections::getData($section_slug)->first();
        $languages = $this->languages;

        return view('admin.sections.setup-sections.banner-section',compact(
            'page_title',
            'data',
            'languages',
            'slug',
        ));
    }

    /**
     * Mehtod for update banner section information
     * @param string $slug
     * @param \Illuminate\Http\Request  $request
     */
    public function bannerUpdate(Request $request,$slug) {
        $basic_field_name = [
            'left_heading' => "required|string|max:100",
            'left_sub_heading' => "required|string|max:255",
            'left_input_one' => "required|string",
            'left_input_two' => "required|string",
            'left_details' => "required|string",
            'left_button' => "required|string",

            'right_heading' => "required|string",
            'right_details' => "required|string",
            'right_input_one' => "required|string",
            'right_input_two' => "required|string",
            'right_button' => "required|string",
        ];

        $slug = Str::slug(SiteSectionConst::BANNER_SECTION);
        $section = SiteSections::where("key",$slug)->first();
        $data['language']  = $this->contentValidate($request,$basic_field_name);
        $update_data['value']  = $data;
        $update_data['key']    = $slug;

        try{
            SiteSections::updateOrCreate(['key' => $slug],$update_data);
        }catch(Exception $e) {
            return back()->with(['error' => ['Something went wrong! Please try again.']]);
        }

        return back()->with(['success' => ['Section updated successfully!']]);
    }

   /**
     * Mehtod for show brand section page
     * @param string $slug
     * @return view
     */
    public function brandView($slug) {
        $page_title = "Brand Section";
        $section_slug = Str::slug(SiteSectionConst::BRAND_SECTION);
        $data = SiteSections::getData($section_slug)->first();
        $languages = $this->languages;

        return view('admin.sections.setup-sections.brand-section',compact(
            'page_title',
            'data',
            'languages',
            'slug',
        ));
    }
    public function brandUpdate(Request $request,$slug) {
        $basic_field_name = [
            'heading' => "nullable|string|max:100", 
        ];

        $slug = Str::slug(SiteSectionConst::BRAND_SECTION);
        $section = SiteSections::where("key",$slug)->first();
        $data['language']  = $this->contentValidate($request,$basic_field_name);
        $update_data['value']  = $data;
        $update_data['key']    = $slug;

        try{
            SiteSections::updateOrCreate(['key' => $slug],$update_data);
        }catch(Exception $e) {
            return back()->with(['error' => ['Something went wrong! Please try again.']]);
        }

        return back()->with(['success' => ['Section updated successfully!']]);
    }
    public function brandItemStore(Request $request,$slug) {  
        $validator = Validator::make($request->all(), [
            'front_image' => 'required',
        ]);
    
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('modal','brand-add');
        }
    
        $validated = $validator->validate();
    
        $basic_field_name = [
             
        ];
        $language_wise_data = $this->contentValidate($request,$basic_field_name,"brand-add");
        if($language_wise_data instanceof RedirectResponse) return $language_wise_data;
        $slug = Str::slug(SiteSectionConst::BRAND_SECTION);
        $section = SiteSections::where("key",$slug)->first();
    
        if($section != null) {
            $section_data = json_decode(json_encode($section->value),true);
        }else {
            $section_data = [];
        }
        $unique_id = uniqid();
        
        $section_data['items'][$unique_id]['language'] = $language_wise_data;
        $section_data['items'][$unique_id]['id'] = $unique_id;
        //image upload 
        if($request->hasFile('front_image')) {
            $image = get_files_from_fileholder($request,'front_image');
            $upload = upload_files_from_path_dynamic($image,'site-section'); 
            $section_data['items'][$unique_id]['front_image'] = $upload;
        } 
        
        $update_data['key'] = $slug;
        $update_data['value']   = $section_data;
    
        try{
            SiteSections::updateOrCreate(['key' => $slug],$update_data);
        }catch(Exception $e) {
            return back()->with(['error' => ['Something went worng! Please try again']]);
        }
    
        return back()->with(['success' => ['Item added successfully!']]);
    }
    public function brandItemUpdate(Request $request,$slug) {
        $validator = Validator::make($request->all(), [
            'front_image_edit' => 'nullable',
        ]);
    
        if($validator->fails()) {
            return back()->withErrors($validator)->withInput()->with('modal','brand-section-edit');
        }
    
        $request->validate([
            'target'    => "required|string",
        ]);
    
        $basic_field_name = [
             
        ];
    
        $slug = Str::slug(SiteSectionConst::BRAND_SECTION);
        $section = SiteSections::getData($slug)->first();
        if(!$section) return back()->with(['error' => ['Section not found!']]);
        $section_values = json_decode(json_encode($section->value),true);
        if(!isset($section_values['items'])) return back()->with(['error' => ['Section item not found!']]);
        if(!array_key_exists($request->target,$section_values['items'])) return back()->with(['error' => ['Section item is invalid!']]);
        
    
        $language_wise_data = $this->contentValidate($request,$basic_field_name,"brand-edit");
        if($language_wise_data instanceof RedirectResponse) return $language_wise_data;
        
        $language_wise_data = array_map(function($language) {
            return replace_array_key($language,"_edit");
        },$language_wise_data);
        
        $section_values['items'][$request->target]['language'] = $language_wise_data;
            //image upload 
            if($request->hasFile('front_image_edit')) {
                $image = get_files_from_fileholder($request,'front_image_edit');
                $upload = upload_files_from_path_dynamic($image,'site-section'); 
                $section_values['items'][$request->target]['front_image'] = $upload;
            } 

        try{
            $section->update([
                'value' => $section_values,
            ]);
        }catch(Exception $e) {
            return back()->with(['error' => ['Something went worng! Please try again']]);
        }
    
        return back()->with(['success' => ['Information updated successfully!']]);
    }
    public function brandItemDelete(Request $request,$slug) { 
        $request->validate([
            'target'    => 'required|string',
        ]);
        $slug = Str::slug(SiteSectionConst::BRAND_SECTION);
        $section = SiteSections::getData($slug)->first();
        if(!$section) return back()->with(['error' => ['Section not found!']]);
        $section_values = json_decode(json_encode($section->value),true);
        if(!isset($section_values['items'])) return back()->with(['error' => ['Section item not found!']]);
        if(!array_key_exists($request->target,$section_values['items'])) return back()->with(['error' => ['Section item is invalid!']]);
        try{
            unset($section_values['items'][$request->target]);
            $section->update([
                'value'     => $section_values,
            ]);
        }catch(Exception $e) {
            return  $e->getMessage();
        }
    
        return back()->with(['success' => ['Item delete successfully!']]);
    }



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
//======================Faq section Start =================================
public function faqView($slug) {
    $page_title = "FAQ Section";
    $section_slug = Str::slug(SiteSectionConst::FAQ_SECTION);
    $data = SiteSections::getData($section_slug)->first();
    $languages = $this->languages;

    return view('admin.sections.setup-sections.faq-section',compact(
        'page_title',
        'data',
        'languages',
        'slug',
    ));
}

public function faqUpdate(Request $request,$slug) {
    $basic_field_name = [
        'heading' => "required|string|max:100",
        'sub_heading' => "required|string|max:200",
        'details' => "required|string",

    ];

    $slug = Str::slug(SiteSectionConst::FAQ_SECTION);
    $section = SiteSections::where("key",$slug)->first();
    if($section != null) {
        $data = json_decode(json_encode($section->value),true);
    }else {
        $data = [];
    }
    
    $data['language']  = $this->contentValidate($request,$basic_field_name);
    
    $update_data['key']    = $slug;
    $update_data['value']  = $data;

    try{
        SiteSections::updateOrCreate(['key' => $slug],$update_data);
    }catch(Exception $e) {
        return back()->with(['error' => ['Something went worng! Please try again.']]);
    }

    return back()->with(['success' => ['Section updated successfully!']]);
}

public function faqItemStore(Request $request,$slug) {
    $basic_field_name = [
        'question' => "required|string|max:200",
        'answer' => "required|string",
    ];


    $language_wise_data = $this->contentValidate($request,$basic_field_name,"faq-add");
    if($language_wise_data instanceof RedirectResponse) return $language_wise_data;
    $slug = Str::slug(SiteSectionConst::FAQ_SECTION);
    $section = SiteSections::where("key",$slug)->first();

    if($section != null) {
        $section_data = json_decode(json_encode($section->value),true);
    }else {
        $section_data = [];
    }
    $unique_id = uniqid();
    
    $section_data['items'][$unique_id]['language'] = $language_wise_data;
    $section_data['items'][$unique_id]['id'] = $unique_id;
    
    $update_data['key'] = $slug;
    $update_data['value']   = $section_data;

    try{
        SiteSections::updateOrCreate(['key' => $slug],$update_data);
    }catch(Exception $e) {
        return back()->with(['error' => ['Something went worng! Please try again']]);
    }

    return back()->with(['success' => ['Item added successfully!']]);
}


public function faqItemUpdate(Request $request,$slug) {
    $request->validate([
        'target'    => "required|string",
    ]);

    $basic_field_name = [
        'question_edit'     => "required|string|max:100",
        'answer_edit'     => "required|string",
    ];

    $slug = Str::slug(SiteSectionConst::FAQ_SECTION);
    $section = SiteSections::getData($slug)->first();
    if(!$section) return back()->with(['error' => ['Section not found!']]);
    $section_values = json_decode(json_encode($section->value),true);
    if(!isset($section_values['items'])) return back()->with(['error' => ['Section item not found!']]);
    if(!array_key_exists($request->target,$section_values['items'])) return back()->with(['error' => ['Section item is invalid!']]);
    

    $language_wise_data = $this->contentValidate($request,$basic_field_name,"faq-edit");
    if($language_wise_data instanceof RedirectResponse) return $language_wise_data;
    
    $language_wise_data = array_map(function($language) {
        return replace_array_key($language,"_edit");
    },$language_wise_data);
    
    $section_values['items'][$request->target]['language'] = $language_wise_data;
    try{
        $section->update([
            'value' => $section_values,
        ]);
    }catch(Exception $e) {
        return back()->with(['error' => ['Something went worng! Please try again']]);
    }

    return back()->with(['success' => ['Information updated successfully!']]);
}

public function faqItemDelete(Request $request,$slug) {
    $request->validate([
        'target'    => 'required|string',
    ]);
    $slug = Str::slug(SiteSectionConst::FAQ_SECTION);
    $section = SiteSections::getData($slug)->first();
    if(!$section) return back()->with(['error' => ['Section not found!']]);
    $section_values = json_decode(json_encode($section->value),true);
    if(!isset($section_values['items'])) return back()->with(['error' => ['Section item not found!']]);
    if(!array_key_exists($request->target,$section_values['items'])) return back()->with(['error' => ['Section item is invalid!']]);
    try{
        unset($section_values['items'][$request->target]);
        $section->update([
            'value'     => $section_values,
        ]);
    }catch(Exception $e) {
        return  $e->getMessage();
    }

    return back()->with(['success' => ['Item delete successfully!']]);
}
//=======================Faq  Section End===================================
//==========================About Section Start====================================
public function aboutView($slug){
    $page_title = "About Section";
    $section_slug = Str::slug(SiteSectionConst::ABOUT_SECTION);
    $data = SiteSections::getData($section_slug)->first();
    $languages = $this->languages;

    return view('admin.sections.setup-sections.about-section',compact(
        'page_title',
        'data',
        'languages',
        'slug',
    ));
}
public function aboutUpdate(Request $request,$slug) {
    $basic_field_name = [
        'heading'     => "required|string|max:100",
        'sub_heading' => "required|string|max:200",
        'details'     => "required|string",
    ];

    $slug = Str::slug(SiteSectionConst::ABOUT_SECTION);
    $section = SiteSections::where("key",$slug)->first();
    if($section != null) {
        $data = json_decode(json_encode($section->value),true);
    }else {
        $data = [];
    }
    
    $data['language']  = $this->contentValidate($request,$basic_field_name);
    //image upload 
    if($request->hasFile('image')) {
        $image = get_files_from_fileholder($request,'image');
        $upload = upload_files_from_path_dynamic($image,'site-section');
        $data['image'] = $upload;
    }
    $update_data['key']    = $slug;
    $update_data['value']  = $data;

    try{
        SiteSections::updateOrCreate(['key' => $slug],$update_data);
    }catch(Exception $e) {
        return back()->with(['error' => ['Something went worng! Please try again.']]);
    }

    return back()->with(['success' => ['Section updated successfully!']]);
}
public function aboutItemStore(Request $request,$slug) { 
    $validator = Validator::make($request->all(), [
        'icon' => 'required|string'
    ]);

    if($validator->fails()) {
        return back()->withErrors($validator)->withInput()->with('modal','service-section-add');
    }

    $validated = $validator->validate();

    $basic_field_name = [
        'title' => "required|string|max:200", 
    ];

    $language_wise_data = $this->contentValidate($request,$basic_field_name,"about-add");
    if($language_wise_data instanceof RedirectResponse) return $language_wise_data;
    $slug = Str::slug(SiteSectionConst::ABOUT_SECTION);
    $section = SiteSections::where("key",$slug)->first();

    if($section != null) {
        $section_data = json_decode(json_encode($section->value),true);
    }else {
        $section_data = [];
    }
    $unique_id = uniqid();
    
    $section_data['items'][$unique_id]['language'] = $language_wise_data;
    $section_data['items'][$unique_id]['id'] = $unique_id;
    $section_data['items'][$unique_id]['icon'] = $validated['icon'];
    
    $update_data['key'] = $slug;
    $update_data['value']   = $section_data;

    try{
        SiteSections::updateOrCreate(['key' => $slug],$update_data);
    }catch(Exception $e) {
        return back()->with(['error' => ['Something went worng! Please try again']]);
    }

    return back()->with(['success' => ['Item added successfully!']]);
}

public function aboutItemUpdate(Request $request,$slug) {
    $validator = Validator::make($request->all(), [
        'icon_edit' => 'required|string'
    ]);

    if($validator->fails()) {
        return back()->withErrors($validator)->withInput()->with('modal','service-section-edit');
    }

    $validated = $validator->validate();

    $request->validate([
        'target'    => "required|string",
    ]);

    $basic_field_name = [
        'title_edit'     => "required|string|max:100", 
    ];

    $slug = Str::slug(SiteSectionConst::ABOUT_SECTION);
    $section = SiteSections::getData($slug)->first();
    if(!$section) return back()->with(['error' => ['Section not found!']]);
    $section_values = json_decode(json_encode($section->value),true);
    if(!isset($section_values['items'])) return back()->with(['error' => ['Section item not found!']]);
    if(!array_key_exists($request->target,$section_values['items'])) return back()->with(['error' => ['Section item is invalid!']]);
    

    $language_wise_data = $this->contentValidate($request,$basic_field_name,"about-edit");
    if($language_wise_data instanceof RedirectResponse) return $language_wise_data;
    
    $language_wise_data = array_map(function($language) {
        return replace_array_key($language,"_edit");
    },$language_wise_data);
    
    $section_values['items'][$request->target]['language'] = $language_wise_data;
        $section_values['items'][$request->target]['icon'] = $validated['icon_edit'];
    try{
        $section->update([
            'value' => $section_values,
        ]);
    }catch(Exception $e) {
        return back()->with(['error' => ['Something went worng! Please try again']]);
    }

    return back()->with(['success' => ['Information updated successfully!']]);
}
public function aboutItemDelete(Request $request,$slug) {
    $request->validate([
        'target'    => 'required|string',
    ]);
    $slug = Str::slug(SiteSectionConst::ABOUT_SECTION);
    $section = SiteSections::getData($slug)->first();
    if(!$section) return back()->with(['error' => ['Section not found!']]);
    $section_values = json_decode(json_encode($section->value),true);
    if(!isset($section_values['items'])) return back()->with(['error' => ['Section item not found!']]);
    if(!array_key_exists($request->target,$section_values['items'])) return back()->with(['error' => ['Section item is invalid!']]);
    try{
        unset($section_values['items'][$request->target]);
        $section->update([
            'value'     => $section_values,
        ]);
    }catch(Exception $e) {
        return  $e->getMessage();
    }

    return back()->with(['success' => ['Item delete successfully!']]);
}
//==========================About Section End====================================
//============================Service Section Start=================================
public function serviceView($slug){
    $page_title = "Service Section";
    $section_slug = Str::slug(SiteSectionConst::SERVICE_SECTION);
    $data = SiteSections::getData($section_slug)->first();
    $languages = $this->languages;

    return view('admin.sections.setup-sections.service-section',compact(
        'page_title',
        'data',
        'languages',
        'slug',
    ));
}
public function serviceUpdate(Request $request,$slug) {
    $basic_field_name = [
        'heading'     => "required|string|max:100",
        'sub_heading' => "required|string|max:200",
    ];

    $slug = Str::slug(SiteSectionConst::SERVICE_SECTION);
    $section = SiteSections::where("key",$slug)->first();
    if($section != null) {
        $data = json_decode(json_encode($section->value),true);
    }else {
        $data = [];
    }
    
    $data['language']  = $this->contentValidate($request,$basic_field_name); 
    $update_data['key']    = $slug;
    $update_data['value']  = $data;

    try{
        SiteSections::updateOrCreate(['key' => $slug],$update_data);
    }catch(Exception $e) {
        return back()->with(['error' => ['Something went worng! Please try again.']]);
    }

    return back()->with(['success' => ['Section updated successfully!']]);
}
public function serviceItemStore(Request $request,$slug) { 
    $validator = Validator::make($request->all(), [
        'icon' => 'required|string'
    ]);

    if($validator->fails()) {
        return back()->withErrors($validator)->withInput()->with('modal','service-section-add');
    }

    $validated = $validator->validate();

    $basic_field_name = [
        'title' => "required|string|max:200",
        'details'     => "required|string", 
    ];


    $language_wise_data = $this->contentValidate($request,$basic_field_name,"service-add");
    if($language_wise_data instanceof RedirectResponse) return $language_wise_data;
    $slug = Str::slug(SiteSectionConst::SERVICE_SECTION);
    $section = SiteSections::where("key",$slug)->first();

    if($section != null) {
        $section_data = json_decode(json_encode($section->value),true);
    }else {
        $section_data = [];
    }
    $unique_id = uniqid();
    
    $section_data['items'][$unique_id]['language'] = $language_wise_data;
    $section_data['items'][$unique_id]['id'] = $unique_id;
    $section_data['items'][$unique_id]['icon'] = $validated['icon'];
    
    $update_data['key'] = $slug;
    $update_data['value']   = $section_data;

    try{
        SiteSections::updateOrCreate(['key' => $slug],$update_data);
    }catch(Exception $e) {
        return back()->with(['error' => ['Something went worng! Please try again']]);
    }

    return back()->with(['success' => ['Item added successfully!']]);
}

public function serviceItemUpdate(Request $request,$slug) {
    $validator = Validator::make($request->all(), [
        'icon_edit' => 'required|string'
    ]);

    if($validator->fails()) {
        return back()->withErrors($validator)->withInput()->with('modal','service-section-edit');
    }

    $validated = $validator->validate();

    $request->validate([
        'target'    => "required|string",
    ]);

    $basic_field_name = [
        'title_edit'     => "required|string|max:100", 
        'details_edit'     => "required|string", 
    ];

    $slug = Str::slug(SiteSectionConst::SERVICE_SECTION);
    $section = SiteSections::getData($slug)->first();
    if(!$section) return back()->with(['error' => ['Section not found!']]);
    $section_values = json_decode(json_encode($section->value),true);
    if(!isset($section_values['items'])) return back()->with(['error' => ['Section item not found!']]);
    if(!array_key_exists($request->target,$section_values['items'])) return back()->with(['error' => ['Section item is invalid!']]);
    

    $language_wise_data = $this->contentValidate($request,$basic_field_name,"about-edit");
    if($language_wise_data instanceof RedirectResponse) return $language_wise_data;
    
    $language_wise_data = array_map(function($language) {
        return replace_array_key($language,"_edit");
    },$language_wise_data);
    
    $section_values['items'][$request->target]['language'] = $language_wise_data;
        $section_values['items'][$request->target]['icon'] = $validated['icon_edit'];
    try{
        $section->update([
            'value' => $section_values,
        ]);
    }catch(Exception $e) {
        return back()->with(['error' => ['Something went worng! Please try again']]);
    }

    return back()->with(['success' => ['Information updated successfully!']]);
}
public function serviceItemDelete(Request $request,$slug) {
    $request->validate([
        'target'    => 'required|string',
    ]);
    $slug = Str::slug(SiteSectionConst::SERVICE_SECTION);
    $section = SiteSections::getData($slug)->first();
    if(!$section) return back()->with(['error' => ['Section not found!']]);
    $section_values = json_decode(json_encode($section->value),true);
    if(!isset($section_values['items'])) return back()->with(['error' => ['Section item not found!']]);
    if(!array_key_exists($request->target,$section_values['items'])) return back()->with(['error' => ['Section item is invalid!']]);
    try{
        unset($section_values['items'][$request->target]);
        $section->update([
            'value'     => $section_values,
        ]);
    }catch(Exception $e) {
        return  $e->getMessage();
    }

    return back()->with(['success' => ['Item delete successfully!']]);
}
//============================Service Section End=================================
//==========================feature Section Start====================================
public function featureView($slug){
    $page_title = "Feature Section";
    $section_slug = Str::slug(SiteSectionConst::FEATURE_SECTION);
    $data = SiteSections::getData($section_slug)->first();
    $languages = $this->languages;

    return view('admin.sections.setup-sections.feature-section',compact(
        'page_title',
        'data',
        'languages',
        'slug',
    ));
}
public function featureUpdate(Request $request,$slug) {
    $basic_field_name = [
        'heading'     => "required|string|max:100",
        'sub_heading' => "required|string|max:200",
        'details'     => "required|string",
    ];

    $slug = Str::slug(SiteSectionConst::FEATURE_SECTION);
    $section = SiteSections::where("key",$slug)->first();
    if($section != null) {
        $data = json_decode(json_encode($section->value),true);
    }else {
        $data = [];
    }
    
    $data['language']  = $this->contentValidate($request,$basic_field_name);
    //image upload 
    if($request->hasFile('image')) {
        $image = get_files_from_fileholder($request,'image');
        $upload = upload_files_from_path_dynamic($image,'site-section');
        $data['image'] = $upload;
    }
    $update_data['key']    = $slug;
    $update_data['value']  = $data;

    try{
        SiteSections::updateOrCreate(['key' => $slug],$update_data);
    }catch(Exception $e) {
        return back()->with(['error' => ['Something went worng! Please try again.']]);
    }

    return back()->with(['success' => ['Section updated successfully!']]);
}
public function featureItemStore(Request $request,$slug) { 
    $validator = Validator::make($request->all(), [
        'icon' => 'required|string'
    ]);

    if($validator->fails()) {
        return back()->withErrors($validator)->withInput()->with('modal','service-section-add');
    }

    $validated = $validator->validate();

    $basic_field_name = [
        'title' => "required|string|max:200", 
        'details'     => "required|string", 
    ];


    $language_wise_data = $this->contentValidate($request,$basic_field_name,"about-add");
    if($language_wise_data instanceof RedirectResponse) return $language_wise_data;
    $slug = Str::slug(SiteSectionConst::FEATURE_SECTION);
    $section = SiteSections::where("key",$slug)->first();

    if($section != null) {
        $section_data = json_decode(json_encode($section->value),true);
    }else {
        $section_data = [];
    }
    $unique_id = uniqid();
    
    $section_data['items'][$unique_id]['language'] = $language_wise_data;
    $section_data['items'][$unique_id]['id'] = $unique_id;
    $section_data['items'][$unique_id]['icon'] = $validated['icon'];
    
    $update_data['key'] = $slug;
    $update_data['value']   = $section_data;

    try{
        SiteSections::updateOrCreate(['key' => $slug],$update_data);
    }catch(Exception $e) {
        return back()->with(['error' => ['Something went worng! Please try again']]);
    }

    return back()->with(['success' => ['Item added successfully!']]);
}

public function featureItemUpdate(Request $request,$slug) {
    $validator = Validator::make($request->all(), [
        'icon_edit' => 'required|string'
    ]);

    if($validator->fails()) {
        return back()->withErrors($validator)->withInput()->with('modal','service-section-edit');
    }

    $validated = $validator->validate();

    $request->validate([
        'target'    => "required|string",
    ]);

    $basic_field_name = [
        'title_edit'     => "required|string|max:100", 
        'details_edit'     => "required|string", 
    ];

    $slug = Str::slug(SiteSectionConst::FEATURE_SECTION);
    $section = SiteSections::getData($slug)->first();
    if(!$section) return back()->with(['error' => ['Section not found!']]);
    $section_values = json_decode(json_encode($section->value),true);
    if(!isset($section_values['items'])) return back()->with(['error' => ['Section item not found!']]);
    if(!array_key_exists($request->target,$section_values['items'])) return back()->with(['error' => ['Section item is invalid!']]);
    

    $language_wise_data = $this->contentValidate($request,$basic_field_name,"about-edit");
    if($language_wise_data instanceof RedirectResponse) return $language_wise_data;
    
    $language_wise_data = array_map(function($language) {
        return replace_array_key($language,"_edit");
    },$language_wise_data);
    
    $section_values['items'][$request->target]['language'] = $language_wise_data;
        $section_values['items'][$request->target]['icon'] = $validated['icon_edit'];
    try{
        $section->update([
            'value' => $section_values,
        ]);
    }catch(Exception $e) {
        return back()->with(['error' => ['Something went worng! Please try again']]);
    }

    return back()->with(['success' => ['Information updated successfully!']]);
}
public function featureItemDelete(Request $request,$slug) {
    $request->validate([
        'target'    => 'required|string',
    ]);
    $slug = Str::slug(SiteSectionConst::FEATURE_SECTION);
    $section = SiteSections::getData($slug)->first();
    if(!$section) return back()->with(['error' => ['Section not found!']]);
    $section_values = json_decode(json_encode($section->value),true);
    if(!isset($section_values['items'])) return back()->with(['error' => ['Section item not found!']]);
    if(!array_key_exists($request->target,$section_values['items'])) return back()->with(['error' => ['Section item is invalid!']]);
    try{
        unset($section_values['items'][$request->target]);
        $section->update([
            'value'     => $section_values,
        ]);
    }catch(Exception $e) {
        return  $e->getMessage();
    }

    return back()->with(['success' => ['Item delete successfully!']]);
}
//==========================Feature Section End====================================
//==========================Contact Section Start====================================
public function contactView($slug){
    $page_title = "Contact Section";
    $section_slug = Str::slug(SiteSectionConst::CONTACT_SECTION);
    $data = SiteSections::getData($section_slug)->first();
    $languages = $this->languages;

    return view('admin.sections.setup-sections.contact-section',compact(
        'page_title',
        'data',
        'languages',
        'slug',
    ));
}
public function contactUpdate(Request $request,$slug) {
    $basic_field_name = [
        'heading'     => "required|string|max:100",
        'sub_heading' => "required|string|max:200",
        'right_title' => "required|string|max:200",
        'right_details'     => "required|string",
    ];

    $slug = Str::slug(SiteSectionConst::CONTACT_SECTION);
    $section = SiteSections::where("key",$slug)->first();
    if($section != null) {
        $data = json_decode(json_encode($section->value),true);
    }else {
        $data = [];
    }
    
    $data['language']  = $this->contentValidate($request,$basic_field_name);
    //image upload 
    if($request->hasFile('image')) {
        $image = get_files_from_fileholder($request,'image');
        $upload = upload_files_from_path_dynamic($image,'site-section');
        $data['image'] = $upload;
    }
    $update_data['key']    = $slug;
    $update_data['value']  = $data;

    try{
        SiteSections::updateOrCreate(['key' => $slug],$update_data);
    }catch(Exception $e) {
        return back()->with(['error' => ['Something went worng! Please try again.']]);
    }

    return back()->with(['success' => ['Section updated successfully!']]);
}
public function contactItemStore(Request $request,$slug) { 
    $validator = Validator::make($request->all(), [
        'icon' => 'required|string'
    ]);

    if($validator->fails()) {
        return back()->withErrors($validator)->withInput()->with('modal','service-section-add');
    }

    $validated = $validator->validate();

    $basic_field_name = [
        'title' => "required|string|max:200", 
        'details'     => "required|string", 
    ];


    $language_wise_data = $this->contentValidate($request,$basic_field_name,"about-add");
    if($language_wise_data instanceof RedirectResponse) return $language_wise_data;
    $slug = Str::slug(SiteSectionConst::CONTACT_SECTION);
    $section = SiteSections::where("key",$slug)->first();

    if($section != null) {
        $section_data = json_decode(json_encode($section->value),true);
    }else {
        $section_data = [];
    }
    $unique_id = uniqid();
    
    $section_data['items'][$unique_id]['language'] = $language_wise_data;
    $section_data['items'][$unique_id]['id'] = $unique_id;
    $section_data['items'][$unique_id]['icon'] = $validated['icon'];
    
    $update_data['key'] = $slug;
    $update_data['value']   = $section_data;

    try{
        SiteSections::updateOrCreate(['key' => $slug],$update_data);
    }catch(Exception $e) {
        return back()->with(['error' => ['Something went worng! Please try again']]);
    }

    return back()->with(['success' => ['Item added successfully!']]);
}

public function contactItemUpdate(Request $request,$slug) {
    $validator = Validator::make($request->all(), [
        'icon_edit' => 'required|string'
    ]);

    if($validator->fails()) {
        return back()->withErrors($validator)->withInput()->with('modal','service-section-edit');
    }

    $validated = $validator->validate();

    $request->validate([
        'target'    => "required|string",
    ]);

    $basic_field_name = [
        'title_edit'     => "required|string|max:100", 
        'details_edit'     => "required|string", 
    ];

    $slug = Str::slug(SiteSectionConst::CONTACT_SECTION);
    $section = SiteSections::getData($slug)->first();
    if(!$section) return back()->with(['error' => ['Section not found!']]);
    $section_values = json_decode(json_encode($section->value),true);
    if(!isset($section_values['items'])) return back()->with(['error' => ['Section item not found!']]);
    if(!array_key_exists($request->target,$section_values['items'])) return back()->with(['error' => ['Section item is invalid!']]);
    

    $language_wise_data = $this->contentValidate($request,$basic_field_name,"about-edit");
    if($language_wise_data instanceof RedirectResponse) return $language_wise_data;
    
    $language_wise_data = array_map(function($language) {
        return replace_array_key($language,"_edit");
    },$language_wise_data);
    
    $section_values['items'][$request->target]['language'] = $language_wise_data;
        $section_values['items'][$request->target]['icon'] = $validated['icon_edit'];
    try{
        $section->update([
            'value' => $section_values,
        ]);
    }catch(Exception $e) {
        return back()->with(['error' => ['Something went worng! Please try again']]);
    }

    return back()->with(['success' => ['Information updated successfully!']]);
}
public function contactItemDelete(Request $request,$slug) {
    $request->validate([
        'target'    => 'required|string',
    ]);
    $slug = Str::slug(SiteSectionConst::CONTACT_SECTION);
    $section = SiteSections::getData($slug)->first();
    if(!$section) return back()->with(['error' => ['Section not found!']]);
    $section_values = json_decode(json_encode($section->value),true);
    if(!isset($section_values['items'])) return back()->with(['error' => ['Section item not found!']]);
    if(!array_key_exists($request->target,$section_values['items'])) return back()->with(['error' => ['Section item is invalid!']]);
    try{
        unset($section_values['items'][$request->target]);
        $section->update([
            'value'     => $section_values,
        ]);
    }catch(Exception $e) {
        return  $e->getMessage();
    }

    return back()->with(['success' => ['Item delete successfully!']]);
}
//==========================Contact Section End====================================
//==========================App Section Start====================================
public function appView($slug){
    $page_title = "App Section";
    $section_slug = Str::slug(SiteSectionConst::APP_SECTION);
    $data = SiteSections::getData($section_slug)->first();
    $languages = $this->languages;

    return view('admin.sections.setup-sections.app-section',compact(
        'page_title',
        'data',
        'languages',
        'slug',
    ));
}
public function appUpdate(Request $request,$slug) {
    $basic_field_name = [
        'heading'     => "required|string|max:100",
        'sub_heading' => "required|string|max:200",
        'details'     => "nullable|string",
    ];

    $slug = Str::slug(SiteSectionConst::APP_SECTION);
    $section = SiteSections::where("key",$slug)->first();
    if($section != null) {
        $data = json_decode(json_encode($section->value),true);
    }else {
        $data = [];
    }
    
    $data['language']  = $this->contentValidate($request,$basic_field_name);
    //image upload 
    if($request->hasFile('image')) {
        $image = get_files_from_fileholder($request,'image');
        $upload = upload_files_from_path_dynamic($image,'site-section');
        $data['image'] = $upload;
    }
    $update_data['key']    = $slug;
    $update_data['value']  = $data;

    try{
        SiteSections::updateOrCreate(['key' => $slug],$update_data);
    }catch(Exception $e) {
        return back()->with(['error' => ['Something went worng! Please try again.']]);
    }

    return back()->with(['success' => ['Section updated successfully!']]);
}
public function appItemStore(Request $request,$slug) { 
    $validator = Validator::make($request->all(), [
        'icon' => 'required|string',
        'link' => 'required|string'
    ]);

    if($validator->fails()) {
        return back()->withErrors($validator)->withInput()->with('modal','service-section-add');
    }

    $validated = $validator->validate();

    $basic_field_name = [
        'title' => "required|string|max:200",  
    ];


    $language_wise_data = $this->contentValidate($request,$basic_field_name,"about-add");
    if($language_wise_data instanceof RedirectResponse) return $language_wise_data;
    $slug = Str::slug(SiteSectionConst::APP_SECTION);
    $section = SiteSections::where("key",$slug)->first();

    if($section != null) {
        $section_data = json_decode(json_encode($section->value),true);
    }else {
        $section_data = [];
    }
    $unique_id = uniqid();
    
    $section_data['items'][$unique_id]['language'] = $language_wise_data;
    $section_data['items'][$unique_id]['id'] = $unique_id;
    $section_data['items'][$unique_id]['icon'] = $validated['icon'];
    $section_data['items'][$unique_id]['link'] = $validated['link'];
    
    $update_data['key'] = $slug;
    $update_data['value']   = $section_data;

    try{
        SiteSections::updateOrCreate(['key' => $slug],$update_data);
    }catch(Exception $e) {
        return back()->with(['error' => ['Something went worng! Please try again']]);
    }

    return back()->with(['success' => ['Item added successfully!']]);
}

public function appItemUpdate(Request $request,$slug) {
    $validator = Validator::make($request->all(), [
        'icon_edit' => 'required|string',
        'link_edit' => 'required|string'
    ]);

    if($validator->fails()) {
        return back()->withErrors($validator)->withInput()->with('modal','service-section-edit');
    }

    $validated = $validator->validate();

    $request->validate([
        'target'    => "required|string",
    ]);

    $basic_field_name = [
        'title_edit'     => "required|string|max:100", 
    ];

    $slug = Str::slug(SiteSectionConst::APP_SECTION);
    $section = SiteSections::getData($slug)->first();
    if(!$section) return back()->with(['error' => ['Section not found!']]);
    $section_values = json_decode(json_encode($section->value),true);
    if(!isset($section_values['items'])) return back()->with(['error' => ['Section item not found!']]);
    if(!array_key_exists($request->target,$section_values['items'])) return back()->with(['error' => ['Section item is invalid!']]);
    

    $language_wise_data = $this->contentValidate($request,$basic_field_name,"about-edit");
    if($language_wise_data instanceof RedirectResponse) return $language_wise_data;
    
    $language_wise_data = array_map(function($language) {
        return replace_array_key($language,"_edit");
    },$language_wise_data);
    
    $section_values['items'][$request->target]['language'] = $language_wise_data;
        $section_values['items'][$request->target]['icon'] = $validated['icon_edit'];
        $section_values['items'][$request->target]['link'] = $validated['link_edit'];
    try{
        $section->update([
            'value' => $section_values,
        ]);
    }catch(Exception $e) {
        return back()->with(['error' => ['Something went worng! Please try again']]);
    }

    return back()->with(['success' => ['Information updated successfully!']]);
}
public function appItemDelete(Request $request,$slug) {
    $request->validate([
        'target'    => 'required|string',
    ]);
    $slug = Str::slug(SiteSectionConst::APP_SECTION);
    $section = SiteSections::getData($slug)->first();
    if(!$section) return back()->with(['error' => ['Section not found!']]);
    $section_values = json_decode(json_encode($section->value),true);
    if(!isset($section_values['items'])) return back()->with(['error' => ['Section item not found!']]);
    if(!array_key_exists($request->target,$section_values['items'])) return back()->with(['error' => ['Section item is invalid!']]);
    try{
        unset($section_values['items'][$request->target]);
        $section->update([
            'value'     => $section_values,
        ]);
    }catch(Exception $e) {
        return  $e->getMessage();
    }

    return back()->with(['success' => ['Item delete successfully!']]);
}
//==========================App Section End====================================
//==========================Testimonial Section Start====================================
public function testimonialView($slug){
    $page_title = "Testimonial Section";
    $section_slug = Str::slug(SiteSectionConst::TESTIMONIAL_SECTION);
    $data = SiteSections::getData($section_slug)->first();
    $languages = $this->languages;

    return view('admin.sections.setup-sections.testimonial-section',compact(
        'page_title',
        'data',
        'languages',
        'slug',
    ));
}
public function testimonialUpdate(Request $request,$slug) {
    $basic_field_name = [
        'heading'     => "required|string|max:100",
        'sub_heading' => "required|string|max:200", 
    ];

    $slug = Str::slug(SiteSectionConst::TESTIMONIAL_SECTION);
    $section = SiteSections::where("key",$slug)->first();
    if($section != null) {
        $data = json_decode(json_encode($section->value),true);
    }else {
        $data = [];
    }
    
    $data['language']  = $this->contentValidate($request,$basic_field_name);
    $update_data['key']    = $slug;
    $update_data['value']  = $data;

    try{
        SiteSections::updateOrCreate(['key' => $slug],$update_data);
    }catch(Exception $e) {
        return back()->with(['error' => ['Something went worng! Please try again.']]);
    }

    return back()->with(['success' => ['Section updated successfully!']]);
}
public function testimonialItemStore(Request $request,$slug) { 
    $validator = Validator::make($request->all(), [
        'user_image' => 'required',
        'user_name' => 'required|string',
        'user_type' => 'required|string',
        'icon_show' => 'required|numeric', 
    ]);

    if($validator->fails()) {
        return back()->withErrors($validator)->withInput()->with('modal','testimonial-section-add');
    }

    $validated = $validator->validate();
    if ($validated['icon_show'] > 5) {
        return redirect()->back()->withInput()->with(['error' => ['The icon must be less then 6.']]);
    }

    $basic_field_name = [
        'details' => "required|string|max:5000",  
    ];


    $language_wise_data = $this->contentValidate($request,$basic_field_name,"testimonial-add");
    if($language_wise_data instanceof RedirectResponse) return $language_wise_data;
    $slug = Str::slug(SiteSectionConst::TESTIMONIAL_SECTION);
    $section = SiteSections::where("key",$slug)->first();

    if($section != null) {
        $section_data = json_decode(json_encode($section->value),true);
    }else {
        $section_data = [];
    }
    $unique_id = uniqid();
    
    $section_data['items'][$unique_id]['language'] = $language_wise_data;
    $section_data['items'][$unique_id]['id'] = $unique_id;
    $section_data['items'][$unique_id]['user_name'] = $validated['user_name'];
    $section_data['items'][$unique_id]['user_type'] = $validated['user_type'];
    $section_data['items'][$unique_id]['icon_show'] = $validated['icon_show'];
     //image upload 
     if($request->hasFile('user_image')) {
        $image = get_files_from_fileholder($request,'user_image');
        $upload = upload_files_from_path_dynamic($image,'site-section');
        $section_data['items'][$unique_id]['user_image'] = $upload;
    }
    
    $update_data['key'] = $slug;
    $update_data['value']   = $section_data;

    try{
        SiteSections::updateOrCreate(['key' => $slug],$update_data);
    }catch(Exception $e) {
        return back()->with(['error' => ['Something went worng! Please try again']]);
    }

    return back()->with(['success' => ['Item added successfully!']]);
}

public function testimonialItemUpdate(Request $request,$slug) {
    $validator = Validator::make($request->all(), [
        'target'    => "required|string",
        'user_image_edit' => 'nullable',
        'user_name_edit' => 'required|string',
        'user_type_edit' => 'required|string',
        'icon_show_edit' => 'required|numeric',
    ]);

    if($validator->fails()) {
        return back()->withErrors($validator)->withInput()->with('modal','testimonial-section-edit');
    }

    $validated = $validator->validate(); 
    if ($validated['icon_show_edit'] > 5) {
        return redirect()->back()->withInput()->with(['error' => ['The icon must be less then 6.']]);
    }
    $basic_field_name = [
        'details_edit'     => "required|string|max:5000", 
    ];

    $slug = Str::slug(SiteSectionConst::TESTIMONIAL_SECTION);
    $section = SiteSections::getData($slug)->first();
    if(!$section) return back()->with(['error' => ['Section not found!']]);
    $section_values = json_decode(json_encode($section->value),true);
    if(!isset($section_values['items'])) return back()->with(['error' => ['Section item not found!']]);
    if(!array_key_exists($request->target,$section_values['items'])) return back()->with(['error' => ['Section item is invalid!']]);
    

    $language_wise_data = $this->contentValidate($request,$basic_field_name,"testimonial-edit");
    if($language_wise_data instanceof RedirectResponse) return $language_wise_data;
    
    $language_wise_data = array_map(function($language) {
        return replace_array_key($language,"_edit");
    },$language_wise_data);
    
    $section_values['items'][$request->target]['language'] = $language_wise_data;
    $section_values['items'][$request->target]['user_name'] = $validated['user_name_edit'];
    $section_values['items'][$request->target]['user_type'] = $validated['user_type_edit'];
    $section_values['items'][$request->target]['icon_show'] = $validated['icon_show_edit']; 
    //image upload 
    if($request->hasFile('user_image_edit')) {
        $image = get_files_from_fileholder($request,'user_image_edit');
        $upload = upload_files_from_path_dynamic($image,'site-section'); 
        $section_values['items'][$request->target]['user_image'] = $upload; 
    }
    try{
        $section->update([
            'value' => $section_values,
        ]);
    }catch(Exception $e) {
        return back()->with(['error' => ['Something went worng! Please try again']]);
    }

    return back()->with(['success' => ['Information updated successfully!']]);
}
public function testimonialItemDelete(Request $request,$slug) {
    $request->validate([
        'target'    => 'required|string',
    ]);
    $slug = Str::slug(SiteSectionConst::TESTIMONIAL_SECTION);
    $section = SiteSections::getData($slug)->first();
    if(!$section) return back()->with(['error' => ['Section not found!']]);
    $section_values = json_decode(json_encode($section->value),true);
    if(!isset($section_values['items'])) return back()->with(['error' => ['Section item not found!']]);
    if(!array_key_exists($request->target,$section_values['items'])) return back()->with(['error' => ['Section item is invalid!']]);
    try{
        unset($section_values['items'][$request->target]);
        $section->update([
            'value'     => $section_values,
        ]);
    }catch(Exception $e) {
        return  $e->getMessage();
    } 
    return back()->with(['success' => ['Item delete successfully!']]);
}
//==========================App Section End====================================
}
