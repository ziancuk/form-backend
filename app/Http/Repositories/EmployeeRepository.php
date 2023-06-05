<?php

namespace App\Http\Repositories;

use Illuminate\Support\Facades\Response as FacadesResponse;
use App\EzHelper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Arr;
use Illuminate\Support\Facades\Auth;

class EmployeeRepository extends Controller
{
    protected $request;

    public function __construct($request=array())
    {
        $this->request = $request;
    }

    public function list()
    {
        $query = new Employee();

        if ($this->request->dataField) {
            $query = Employee::with('employee', 'role')->select($this->request->dataField)->groupBy($this->request->dataField);
        }

        if ($this->request->filterColumn) {

            $parameter = $this->request->filterColumn;

            $query =  $query->where(function($q) use ($parameter) {

                if (count($parameter, COUNT_RECURSIVE) > 3) {
                    for($i=0; $i <= count($parameter) - 1; $i++) {
                        if (is_array($parameter[$i]) == true) {
                            $q->where($parameter[$i][0], 'LIKE', '%'.$parameter[$i][2].'%');
                        }
                    }
                }

                if (count($parameter, COUNT_RECURSIVE) == 3) {
                    $q->where($parameter[0], 'LIKE', '%'.$parameter[2].'%');
                }

            });
        }

        if (!$this->request->skip && !$this->request->take) {
            $this->request->skip = 0;
            $this->request->take = 15;
        }

        if (isset($this->request->custom_filter))
        {
            $custom_filter = $this->request->custom_filter;
            $query =  $query->where(function($q) use ($custom_filter) {
                foreach($custom_filter as $key => $value) {
                    $q->where($key, $value);
                }
            });
        }

        $num = $query->count();

        $query = $query->skip($this->request->skip)->take($this->request->take);

        if ($this->request->orderby) {
            $query = $query->orderByRaw($this->request->orderby);
        }

        $res = $query->get()->all();


        $arr = [];
        foreach ($res as $key => $value) {
            $arr[$key] = $value;
        }

        $data['totalCount'] = $num;
        $data['items'] = $arr;

        return EzHelper::ApiResponse('success', $data);
    }

    public function create()
    {
        $validator = Validator::make($this->request->all(), $this->required('create'));

        if ($validator->fails()) {
            return EzHelper::ApiResponse('error', NULL, $validator->messages()->first());
        }

        $data = [];
        $data = $this->request->all();

        if($this->request->picture) {
            $image = $this->request->file('picture');

            $type = $image->getClientOriginalExtension();
            $name = time() . '.' . $type;
            $image->move(public_path('images/profile'), $name);
        }

        $data['public_id'] = (string)Str::uuid();
        $data['picture'] = $name;
        $data['created_at'] = Carbon::now();
        $data['updated_at'] = Carbon::now();

        Employee::create($data);

        return EzHelper::ApiResponse('success');
    }

    public function update()
    {
        $validator = Validator::make($this->request->all(), $this->required('update'));

        if ($validator->fails()) {
            return EzHelper::ApiResponse('error', NULL, $validator->messages()->first());
        }

        $req = $this->request->all();

        if($this->request->picture) {
            $image = $this->request->file('picture');

            $type = $image->getClientOriginalExtension();
            $name = time() . '.' . $type;
            $image->move(public_path('images/profile'), $name);

            $old = Employee::where('public_id', $this->request->public_id)->first();
            if ($old) {
                $file = public_path('images/profile/' . $old->picture);
                if (file_exists($file)) {
                    unlink($file);
                }
            }

            $req['picture'] = $name;
        }

        Employee::updateOrCreate(['public_id' => $this->request->public_id ], Arr::except($req, ['public_id']));

        return EzHelper::ApiResponse('success');
    }

    public function destroy()
    {
        $validator = Validator::make($this->request->all(), $this->required('destroy'));

        if ($validator->fails()) {
            return EzHelper::ApiResponse('error', NULL, $validator->messages()->first());
        }
        $old = Employee::where('public_id', $this->request->public_id)->first();
        if ($old) {
            $file = public_path('images/profile/' . $old->picture);
            if (file_exists($file)) {
                unlink($file);
            }
        }
        // Employee::where('public_id', $this->request->public_id)->delete();
        Employee::where('public_id', $this->request->public_id)->delete();

        return EzHelper::ApiResponse('success');

    }

    public function first()
    {
        $validator = Validator::make($this->request->all(), $this->required('first'));

        if ($validator->fails()) {
            return EzHelper::ApiResponse('error', NULL, $validator->messages()->first());
        }

        $data = Employee::where('public_id', $this->request['public_id'])->first();

        return EzHelper::ApiResponse('success', $data);

    }

    public function import()
    {
        try {

            $file = $this->request->file('bulk');

            $filename = rand() . '_' . $file->getClientOriginalName();

            $path = storage_path('app/data/import/');

            $file->move($path, $filename);

            if (!File::exists(storage_path('app/data/import/' . $filename))) {
                return response()->json(['error' => 1, 'message' => 'File not found', 'data' => null], 200);
            }

            Excel::import(new AdminImport, storage_path('app/data/import/' . $filename));

            return response()->json(['error' => 0, 'message' => 'Proccess success', 'data' => null], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 1, 'message' => $e->getMessage(), 'data' => null], 200);
        }

        return response()->json(['error' => 1, 'message' => 'Process fail', 'data' => null], 200);

    }

    public function export()
    {
        try {

            $validator = Validator::make($this->request->all(), $this->required('export'));

            if ($validator->fails()) {
                return EzHelper::ApiResponse('error', NULL, $validator->messages()->first());
            }

            $export = new AdminExport($this->request->tgl1, $this->request->tgl2);
            $filename = 'ADMIN ['.$this->request->tgl1.' '.$this->request->tgl2.'].xlsx';

            return Excel::download($export, $filename);

        } catch (\Exception $e) {
            return response()->json(['error' => 1, 'message' => $e->getMessage(), 'data' => null], 200);
        }

        return response()->json(['error' => 1, 'message' => 'Process fail', 'data' => null], 200);

    }

    public function coloumn()
    {
        $models = TableX::where('module', 'ddc4d6ab-f6c0-455a-8e98-b7f8dcbe6a51')->get();

        $gridCol = array();

        $gridCol[0] = array(
            'type' => 'buttons',
            'buttons' => array('edit', 'delete'),
            'width' => 30,
            'alignment' => 'left',
            'allowEditing' => false,
            'allowUpdating' => false,
            'allowFiltering' => false,
            'allowSorting' => false,
            'allowHeaderFiltering' => false,
            'visible' => true,
        );

        $loops = 1;

        foreach ($models as $rows) {

            if ($rows->field !="password")
            {
                if($rows->field == "tanda_tangan")
                {
                    $gridCol[] = array(
                        'dataField' => $rows->field,
                        'caption' => strtoupper($rows->field),
                        'alignment' => 'left',
                        'allowFiltering' => false,
                        'allowSorting' => false,
                        'cellTemplate' => 'cellTemplate',
                        'editCellTemplate' => 'editCellTemplate'
                    );
                } else {
                    $gridCol[] = array(
                        'dataField' => $rows->field,
                        'caption' => strtoupper($rows->field),
                        'alignment' => 'left',
                        'allowEditing' => true,
                        'allowUpdating' => true,
                        'allowFiltering' => true,
                        'allowSorting' => true,
                        'allowHeaderFiltering' => true,
                        'visible' => true,
                    );
                }

                if ($rows->required == 1) {

                    $gridCol[$loops]['validationRules'] = array(
                        array(
                            'type' => 'required',
                            'message' => 'Field '.$rows->field.' is required',
                        )
                    );
                }

                if ($rows['lookup'] > 0) {
                    $gridCol[$loops]['lookup'] = array(
                        'dataSource' => $this->parameters($rows['lookup']),
                        'valueExpr' => 'ID',
                        'displayExpr' => 'Name',
                    );
                }
            }

            $loops++;
        }

        return $gridCol;
    }

    public function required($action) {

        if ($action == "create") {

            $required = [
                'title' => 'required',
                'picture' => 'required|image|mimes:jpg,png,jpeg,gif,svg|max:1024',
                'firstname' => 'required',
                'email' => 'required',
                'phone' => 'required',
                'country' => 'required',
                'city' => 'required',
                'address' => 'required',
                'pos' => 'required',
                'driving' => 'required',
                'nationality' => 'required',
                'place' => 'required',
                'birthdate' => 'required',
            ];

            return $required;

        }

        if ($action == "update") {
            return [
                'public_id' => 'required',
                // 'email' => 'unique:users,email,'.auth()->user()->id.',id'
            ];
        }

        if ($action == "destroy") {
            return [
                'public_id' => 'required',
            ];
        }

        if ($action == "first") {
            return [
                'public_id' => 'required',
            ];
        }

        if ($action == "export") {
            return [
                'tgl1' => 'required',
                'tgl2' => 'required',
            ];
        }

    }

    public function get_image(){
        $filepath = public_path('images/profile/'. $this->request->picture);

        return FacadesResponse::download($filepath); 
    }
}
