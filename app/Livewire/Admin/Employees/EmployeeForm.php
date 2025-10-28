<?php

namespace App\Livewire\Admin\Employees;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Models\Employee;
use App\Models\Tenant;
use App\Models\WorkSchedule;
use App\Models\EmployeeDependent;
use App\Models\EmployeeEmergencyContact;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Layout('layouts.app')]
class EmployeeForm extends Component
{
    use WithFileUploads;

    // Identificador
    public $employeeId;
    public $isEditing = false;

    // Aba 1: Dados Pessoais Básicos
    public $name = '';
    public $email = '';
    public $cpf = '';
    public $rg = '';
    public $rg_issuer = '';
    public $rg_issue_date = '';
    public $birth_date = '';
    public $gender = '';
    public $marital_status = '';
    public $nationality = 'Brasileira';
    public $birth_place = '';
    public $mothers_name = '';
    public $fathers_name = '';
    public $education_level = '';

    // Aba 2: Documentação
    public $ctps = '';
    public $ctps_series = '';
    public $ctps_uf = '';
    public $pis_pasep = '';
    public $voter_registration = '';
    public $voter_zone = '';
    public $voter_section = '';
    public $military_certificate = '';
    public $cnh = '';
    public $cnh_category = '';
    public $cnh_expiry = '';

    // Aba 3: Endereço
    public $phone = '';
    public $zip_code = '';
    public $address = '';
    public $address_number = '';
    public $address_complement = '';
    public $neighborhood = '';
    public $city = '';
    public $state = '';

    // Aba 4: Dados Contratuais
    public $registration_number = '';
    public $admission_date = '';
    public $position = '';
    public $department = '';
    public $contract_type = 'CLT';
    public $contract_number = '';
    public $contract_start_date = '';
    public $contract_end_date = '';
    public $workload_hours = 44.00;
    public $work_schedule_id = '';
    public $cost_center = '';
    public $immediate_supervisor = '';
    public $salary = '';
    public $has_benefits = false;
    public $status = 'active';

    // Aba 5: Dados Bancários
    public $bank_name = '';
    public $bank_code = '';
    public $bank_agency = '';
    public $bank_account = '';
    public $bank_account_type = '';
    public $pix_key = '';

    // Aba 6: Saúde
    public $blood_type = '';
    public $health_insurance = '';
    public $health_insurance_number = '';
    public $allergies = '';
    public $medications = '';
    public $health_conditions = '';
    public $admission_exam_date = '';
    public $next_periodic_exam_date = '';
    public $aso_number = '';

    // Foto
    public $photo;
    public $existingPhoto = '';

    // Observações
    public $notes = '';

    // Dependentes (simplificado para esta versão)
    public $dependents = [];

    // Contatos de Emergência (simplificado para esta versão)
    public $emergencyContacts = [];

    protected function rules()
    {
        return [
            // Dados Básicos - Obrigatórios
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email,' . $this->employeeId,
            'cpf' => 'required|string|size:14|unique:employees,cpf,' . $this->employeeId,
            'phone' => 'required|string',
            'birth_date' => 'required|date',

            // Dados Contratuais - Obrigatórios
            'registration_number' => 'required|string|unique:employees,registration_number,' . $this->employeeId,
            'admission_date' => 'required|date',
            'position' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'salary' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive,vacation,leave',

            // Endereço - Obrigatórios
            'zip_code' => 'required|string',
            'address' => 'required|string',
            'address_number' => 'required|string',
            'neighborhood' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string|size:2',

            // Campos Opcionais
            'rg' => 'nullable|string',
            'rg_issuer' => 'nullable|string',
            'rg_issue_date' => 'nullable|date',
            'gender' => 'nullable|in:M,F,Outro',
            'marital_status' => 'nullable|in:Solteiro(a),Casado(a),Divorciado(a),Viúvo(a),União Estável',
            'nationality' => 'nullable|string',
            'birth_place' => 'nullable|string',
            'mothers_name' => 'nullable|string',
            'fathers_name' => 'nullable|string',
            'education_level' => 'nullable|string',

            'ctps' => 'nullable|string',
            'ctps_series' => 'nullable|string',
            'ctps_uf' => 'nullable|string|size:2',
            'pis_pasep' => 'nullable|string',
            'voter_registration' => 'nullable|string',
            'voter_zone' => 'nullable|string',
            'voter_section' => 'nullable|string',
            'military_certificate' => 'nullable|string',
            'cnh' => 'nullable|string',
            'cnh_category' => 'nullable|string',
            'cnh_expiry' => 'nullable|date',

            'address_complement' => 'nullable|string',

            'contract_type' => 'required|in:CLT,PJ,Estágio,Temporário,Autônomo',
            'contract_number' => 'nullable|string',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'workload_hours' => 'required|numeric|min:0',
            'work_schedule_id' => 'nullable|exists:work_schedules,id',
            'cost_center' => 'nullable|string',
            'immediate_supervisor' => 'nullable|string',
            'has_benefits' => 'boolean',

            'bank_name' => 'nullable|string',
            'bank_code' => 'nullable|string',
            'bank_agency' => 'nullable|string',
            'bank_account' => 'nullable|string',
            'bank_account_type' => 'nullable|in:Corrente,Poupança,Salário',
            'pix_key' => 'nullable|string',

            'blood_type' => 'nullable|string',
            'health_insurance' => 'nullable|string',
            'health_insurance_number' => 'nullable|string',
            'allergies' => 'nullable|string',
            'medications' => 'nullable|string',
            'health_conditions' => 'nullable|string',
            'admission_exam_date' => 'nullable|date',
            'next_periodic_exam_date' => 'nullable|date',
            'aso_number' => 'nullable|string',

            'photo' => 'nullable|image|max:2048',
            'notes' => 'nullable|string',
        ];
    }

    protected $messages = [
        // Dados Pessoais
        'name.required' => 'O nome é obrigatório.',
        'email.required' => 'O e-mail é obrigatório.',
        'email.email' => 'Digite um e-mail válido.',
        'email.unique' => 'Este e-mail já está cadastrado.',
        'cpf.required' => 'O CPF é obrigatório.',
        'cpf.size' => 'O CPF deve ter 14 caracteres (com pontos e traço).',
        'cpf.unique' => 'Este CPF já está cadastrado.',
        'phone.required' => 'O telefone é obrigatório.',
        'birth_date.required' => 'A data de nascimento é obrigatória.',

        // Endereço
        'zip_code.required' => 'O CEP é obrigatório.',
        'address.required' => 'O endereço é obrigatório.',
        'address_number.required' => 'O número é obrigatório.',
        'neighborhood.required' => 'O bairro é obrigatório.',
        'city.required' => 'A cidade é obrigatória.',
        'state.required' => 'O estado é obrigatório.',
        'state.size' => 'O estado deve ter 2 caracteres.',

        // Dados Contratuais
        'registration_number.required' => 'O número de matrícula é obrigatório.',
        'registration_number.unique' => 'Este número de matrícula já está cadastrado.',
        'admission_date.required' => 'A data de admissão é obrigatória.',
        'position.required' => 'O cargo é obrigatório.',
        'department.required' => 'O departamento é obrigatório.',
        'salary.required' => 'O salário é obrigatório.',
        'salary.numeric' => 'O salário deve ser um valor numérico.',
        'salary.min' => 'O salário não pode ser negativo.',
        'contract_type.required' => 'O tipo de contrato é obrigatório.',
        'workload_hours.required' => 'A carga horária é obrigatória.',
        'workload_hours.numeric' => 'A carga horária deve ser um valor numérico.',
        'workload_hours.min' => 'A carga horária não pode ser negativa.',
        'status.required' => 'O status é obrigatório.',

        // Outros
        'photo.image' => 'O arquivo deve ser uma imagem.',
        'photo.max' => 'A imagem não pode ser maior que 2MB.',
    ];

    public function mount($employeeId = null)
    {
        if ($employeeId) {
            $this->isEditing = true;
            $this->employeeId = $employeeId;
            $this->loadEmployee();
        } else {
            // Gerar número de matrícula automaticamente
            $this->registration_number = 'MAT' . date('Ymd') . rand(1000, 9999);
        }
    }

    public function loadEmployee()
    {
        $employee = Employee::with(['dependents', 'emergencyContacts'])->findOrFail($this->employeeId);

        // Dados Pessoais
        $this->name = $employee->name;
        $this->email = $employee->email;
        $this->cpf = $employee->cpf;
        $this->rg = $employee->rg;
        $this->rg_issuer = $employee->rg_issuer;
        $this->rg_issue_date = $employee->rg_issue_date?->format('Y-m-d');
        $this->birth_date = $employee->birth_date?->format('Y-m-d');
        $this->gender = $employee->gender;
        $this->marital_status = $employee->marital_status;
        $this->nationality = $employee->nationality;
        $this->birth_place = $employee->birth_place;
        $this->mothers_name = $employee->mothers_name;
        $this->fathers_name = $employee->fathers_name;
        $this->education_level = $employee->education_level;

        // Documentação
        $this->ctps = $employee->ctps;
        $this->ctps_series = $employee->ctps_series;
        $this->ctps_uf = $employee->ctps_uf;
        $this->pis_pasep = $employee->pis_pasep;
        $this->voter_registration = $employee->voter_registration;
        $this->voter_zone = $employee->voter_zone;
        $this->voter_section = $employee->voter_section;
        $this->military_certificate = $employee->military_certificate;
        $this->cnh = $employee->cnh;
        $this->cnh_category = $employee->cnh_category;
        $this->cnh_expiry = $employee->cnh_expiry?->format('Y-m-d');

        // Endereço
        $this->phone = $employee->phone;
        $this->zip_code = $employee->zip_code;
        $this->address = $employee->address;
        $this->address_number = $employee->address_number;
        $this->address_complement = $employee->address_complement;
        $this->neighborhood = $employee->neighborhood;
        $this->city = $employee->city;
        $this->state = $employee->state;

        // Dados Contratuais
        $this->registration_number = $employee->registration_number;
        $this->admission_date = $employee->admission_date?->format('Y-m-d');
        $this->position = $employee->position;
        $this->department = $employee->department;
        $this->contract_type = $employee->contract_type;
        $this->contract_number = $employee->contract_number;
        $this->contract_start_date = $employee->contract_start_date?->format('Y-m-d');
        $this->contract_end_date = $employee->contract_end_date?->format('Y-m-d');
        $this->workload_hours = $employee->workload_hours;
        $this->work_schedule_id = $employee->work_schedule_id;
        $this->cost_center = $employee->cost_center;
        $this->immediate_supervisor = $employee->immediate_supervisor;
        $this->salary = $employee->salary;
        $this->has_benefits = $employee->has_benefits;
        $this->status = $employee->status;

        // Dados Bancários
        $this->bank_name = $employee->bank_name;
        $this->bank_code = $employee->bank_code;
        $this->bank_agency = $employee->bank_agency;
        $this->bank_account = $employee->bank_account;
        $this->bank_account_type = $employee->bank_account_type;
        $this->pix_key = $employee->pix_key;

        // Saúde
        $this->blood_type = $employee->blood_type;
        $this->health_insurance = $employee->health_insurance;
        $this->health_insurance_number = $employee->health_insurance_number;
        $this->allergies = $employee->allergies;
        $this->medications = $employee->medications;
        $this->health_conditions = $employee->health_conditions;
        $this->admission_exam_date = $employee->admission_exam_date?->format('Y-m-d');
        $this->next_periodic_exam_date = $employee->next_periodic_exam_date?->format('Y-m-d');
        $this->aso_number = $employee->aso_number;

        $this->existingPhoto = $employee->photo;
        $this->notes = $employee->notes;
    }

    public function save()
    {
        \Log::info('Iniciando save do funcionário', ['isEditing' => $this->isEditing]);

        $this->validate();

        \Log::info('Validação OK');

        try {
            $data = [
                'tenant_id' => auth()->user()->tenant_id,
                'name' => $this->name,
                'email' => $this->email,
                'cpf' => $this->cpf,
                'rg' => $this->rg,
                'rg_issuer' => $this->rg_issuer,
                'rg_issue_date' => $this->rg_issue_date,
                'birth_date' => $this->birth_date,
                'gender' => $this->gender,
                'marital_status' => $this->marital_status,
                'nationality' => $this->nationality,
                'birth_place' => $this->birth_place,
                'mothers_name' => $this->mothers_name,
                'fathers_name' => $this->fathers_name,
                'education_level' => $this->education_level,
                'ctps' => $this->ctps,
                'ctps_series' => $this->ctps_series,
                'ctps_uf' => $this->ctps_uf,
                'pis_pasep' => $this->pis_pasep,
                'voter_registration' => $this->voter_registration,
                'voter_zone' => $this->voter_zone,
                'voter_section' => $this->voter_section,
                'military_certificate' => $this->military_certificate,
                'cnh' => $this->cnh,
                'cnh_category' => $this->cnh_category,
                'cnh_expiry' => $this->cnh_expiry,
                'phone' => $this->phone,
                'zip_code' => $this->zip_code,
                'address' => $this->address,
                'address_number' => $this->address_number,
                'address_complement' => $this->address_complement,
                'neighborhood' => $this->neighborhood,
                'city' => $this->city,
                'state' => $this->state,
                'registration_number' => $this->registration_number,
                'admission_date' => $this->admission_date,
                'position' => $this->position,
                'department' => $this->department,
                'contract_type' => $this->contract_type,
                'contract_number' => $this->contract_number,
                'contract_start_date' => $this->contract_start_date,
                'contract_end_date' => $this->contract_end_date,
                'workload_hours' => $this->workload_hours,
                'work_schedule_id' => $this->work_schedule_id ?: null,
                'cost_center' => $this->cost_center,
                'immediate_supervisor' => $this->immediate_supervisor,
                'salary' => $this->salary,
                'has_benefits' => $this->has_benefits,
                'status' => $this->status,
                'is_active' => $this->status === 'active',
                'bank_name' => $this->bank_name,
                'bank_code' => $this->bank_code,
                'bank_agency' => $this->bank_agency,
                'bank_account' => $this->bank_account,
                'bank_account_type' => $this->bank_account_type,
                'pix_key' => $this->pix_key,
                'blood_type' => $this->blood_type,
                'health_insurance' => $this->health_insurance,
                'health_insurance_number' => $this->health_insurance_number,
                'allergies' => $this->allergies,
                'medications' => $this->medications,
                'health_conditions' => $this->health_conditions,
                'admission_exam_date' => $this->admission_exam_date,
                'next_periodic_exam_date' => $this->next_periodic_exam_date,
                'aso_number' => $this->aso_number,
                'notes' => $this->notes,
            ];

            // Upload de foto
            if ($this->photo) {
                if ($this->existingPhoto && Storage::exists('public/' . $this->existingPhoto)) {
                    Storage::delete('public/' . $this->existingPhoto);
                }
                $data['photo'] = $this->photo->store('employees', 'public');
            }

            // Gerar código único se não existir
            if (!$this->isEditing) {
                $data['unique_code'] = Str::upper(Str::random(8));
            }

            if ($this->isEditing) {
                $employee = Employee::findOrFail($this->employeeId);
                $employee->update($data);
                \Log::info('Colaborador atualizado', ['id' => $employee->id]);
                session()->flash('success', 'Colaborador atualizado com sucesso!');
            } else {
                $employee = Employee::create($data);
                \Log::info('Colaborador criado', ['id' => $employee->id]);
                session()->flash('success', 'Colaborador cadastrado com sucesso!');
            }

            return redirect()->route('admin.employees.index');

        } catch (\Exception $e) {
            \Log::error('Erro ao salvar colaborador', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            session()->flash('error', 'Erro ao salvar colaborador: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $workSchedules = WorkSchedule::where('tenant_id', auth()->user()->tenant_id)->get();

        return view('livewire.admin.employees.employee-form', [
            'workSchedules' => $workSchedules,
        ])->title($this->isEditing ? 'Editar Colaborador' : 'Novo Colaborador');
    }
}
