<?php

namespace ten31\Otms\Controllers;

use Illuminate\Support\Facades\Validator;
use ten31\Otms\Models\Lead;
use Lang;
use Log;
use Ten31\Dictionnary\Models\Settings;


class FormHandler
{

    private $userfields = [
        'name' => 'name',
        'companyName' => 'company',
        'mobile' => 'phone',
        'email' => 'email',
        'leadSourceId' => 'leadSourceId',
        'dbcVarchar3' => 'note',
        'dbcSelect6' => 'service',
    ];

    private $rules = [
        'name' => 'required|max:100',
        'email' => 'required|email',
        'phone' => 'required|integer',
        'company' => 'required|max:100',
        'leadSourceId' => 'required|numeric',
        'service' => 'required|numeric',
    ];

    private $messages = [];

    public function __construct(){
        $this->messages = [
            'required' => Lang::get('ten31.otms::lang.validation.required'),
	   'name.required' =>  Lang::get('ten31.otms::lang.validation.name_required'),
            'company.required' =>  Lang::get('ten31.otms::lang.validation.company_required'),
            'phone.required' =>  Lang::get('ten31.otms::lang.validation.mobile_required'),
            'email.required' =>  Lang::get('ten31.otms::lang.validation.email_required'),
            'leadSourceId.required' => Lang::get('ten31.otms::lang.validation.leadSourceId_required'),
            'service.required' => Lang::get('ten31.otms::lang.validation.service_required'),
            'success' => [
                'demo' => Lang::get('ten31.otms::lang.messages.demo_success'),
                'register' => Lang::get('ten31.otms::lang.messages.demo_success'),
                'download' => Lang::get('ten31.otms::lang.messages.download_success'),
            ],
        ];
        //debug($this->messages);
    }

    private function validate($data)
    {
        return \Validator::make($data, $this->rules, $this->messages);
    }

    private function createLead($data)
    {
        $lead = new Lead();
        $lead->name = $data['name'];
        $lead->company_name = $data['companyName'];
        $lead->mobile = $data['mobile'];
        $lead->email = $data['email'];
        $lead->lead_source_id = $data['leadSourceId'];
        $lead->service = $data['dbcSelect6'];
        $lead->note = $data['dbcVarchar3'];
        $lead->xsy_response = $data['response'];
        $lead->save();
    }


    public function sendXiaoshouyiLead ($form_data=null, $source='demo')
    {

        $validator = $this->validate($form_data);

        if($validator->fails()){
            $result['status'] = 'error';
            foreach ($validator->messages()->all() as $message) {
                $result['messages'][] = $message;
            }
            return $result;
        }

        // non-user fields required by XSY
        $lead = [
            'ownerId' => Settings::get('xsy_ownerid'),
            'dimDepart' => Settings::get('xsy_dimdepart'),
            'dbcVarchar11' => $source,
            'dbcSelect4' => 11,
            'state' => '请输入省份',
            'dbcVarchar5' => '请输入城市',
            'dbcSelect1' => 3,
        ];

        // getting user fields that were validated
        foreach($this->userfields as $key => $value){
            if(isset($form_data[$value])){
                $lead[$key] = $form_data[$value];
            }
        }

        $lead_data = [
            'public'=>true,
            'record'=> $lead,
        ];
        $form_encoded = json_encode($lead_data);

        $xsy = new XiaoshouyiClient($form_encoded);

        $result = $xsy->sendRequest();

        $lead['response'] = $result['response'];
        
        
        $reponse = json_decode($result['response']);
        Log::info("Form Handler : reponse of xiaoshouyi => ".json_encode($reponse));
        if(!isset($reponse->error_code)){
        	Log::info('Form Handler : Xiaoshouyi answer correct, register the lead in the db');
        	$this->createLead($lead);
        }else{
        	//if we have an error code in the xiaoshouyo answer, that mean the lead has not been registered, 
        	//so we dont save it as a new lead in the backend
        	// we log the lead and the error 
        	Log::info('Form Handler : Error code during the registration of a user => '.json_encode($lead));
        }
        

        if($result['status']=='success') $result['messages'][] = $this->messages['success'][$source];

        return $result;

    }









}
