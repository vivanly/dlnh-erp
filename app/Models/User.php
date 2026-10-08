<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_code',
        'phone',
        'address',
        'status',
        'department_id',
        'locale',
        'position',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Quan hệ: Một nhân viên thuộc về một phòng ban
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // ==========================================
    // 1. HÀM HELPER KIỂM TRA PHÒNG BAN (THEO MÃ CODE)
    // ==========================================

    public function isDepartment($code)
    {
        return optional($this->department)->code === strtoupper($code);
    }

    public function isSalesDepartment()
    {
        return $this->isITDepartment()
            || $this->isDepartment('KINH-DOANH') || in_array($this->role, ['sales', 'sales_staff', 'sales_director']);
    }

    public function isPlanningDepartment()
    {
        return $this->isITDepartment()
            || $this->isDepartment('KE-HOACH')
            || $this->isDepartment('KẾ HOẠCH')
            || in_array($this->role, ['planning', 'planner', 'production_planner', 'planning_manager']);
    }

    public function isWarehouseDepartment()
    {
        return $this->isITDepartment()
            || $this->isDepartment('KHO') || in_array($this->role, ['warehouse', 'warehouse_manager']);
    }

    public function isQADepartment()
    {
        return $this->isITDepartment()
            || $this->isDepartment('QA') || in_array($this->role, ['qa', 'qa_manager']);
    }

    public function isQCDepartment()
    {
        return $this->isITDepartment()
            || $this->isDepartment('QC') || in_array($this->role, ['qc', 'qc_manager']);
    }

    public function isProductionDepartment()
    {
        return $this->isITDepartment()
            || $this->isDepartment('SAN-XUAT') || in_array($this->role, ['production', 'production_manager']);
    }

    public function isAccountingDepartment()
    {
        return $this->isITDepartment()
            || $this->isDepartment('KE-TOAN') || in_array($this->role, ['accounting', 'accountant']);
    }

    public function isDirector()
    {
        return $this->isITDepartment()
            || ($this->position === 'Giám Đốc')
            || in_array($this->role, ['director', 'sales_director', 'bidding_director'], true);
    }

    public function isITDepartment()
    {
        return $this->isDepartment('IT') || $this->role === 'it';
    }

    // ==========================================
    // 2. HÀM HELPER KIỂM TRA VỊ TRÍ (POSITION)
    // ==========================================

    public function isGeneralDirector()
    {
        return $this->isITDepartment()
            || $this->position === 'Tổng Giám Đốc' || $this->role === 'general_director';
    }

    public function isSalesDirector()
    {
        // Phải thuộc phòng Kinh doanh VÀ có vị trí Giám Đốc Kinh Doanh (hoặc role phù hợp)
        return $this->isITDepartment()
            || ($this->isDepartment('KINH-DOANH') && $this->position === 'Giám Đốc') 
            || $this->role === 'sales_director';
    }

    public function isBiddingDirector()
    {
        // Phải thuộc phòng Thầu VÀ có vị trí Giám Đốc Thầu (hoặc role phù hợp)
        return $this->isITDepartment()
            || ($this->isDepartment('THAU') && $this->position === 'Giám Đốc') 
            || $this->role === 'bidding_director';
    }

    public function isDepartmentHead()
    {
        return $this->isITDepartment()
            || $this->position === 'Trưởng Phòng' || $this->role === 'department_head';
    }

    public function isProductionManager()
    {
        return $this->isITDepartment()
            || $this->position === 'Quản Lý Sản Xuất' || $this->role === 'production_manager';
    }

    public function isProductionStaffMember()
    {
        return $this->position === 'Nhân Viên Sản Xuất';
    }

    public function isOfficeStaff()
    {
        return $this->position === 'Nhân Viên Văn Phòng';
    }

    public function isJanitor()
    {
        return $this->position === 'Tạp Vụ';
    }

    public function isDriver()
    {
        return $this->position === 'Lái Xe';
    }

    public function isWarehouseStaffMember()
    {
        return $this->position === 'Nhân Viên Kho';
    }

    // Kiểm tra xem có phải Trưởng phòng kho không
    public function isWarehouseManager()
    {
        return $this->isITDepartment()
            || ($this->isDepartment('KHO') && $this->position === 'Trưởng Phòng') 
            || $this->role === 'warehouse_manager';
    }

    // ==========================================
    // 3. HÀM KẾT HỢP NGHIỆP VỤ (PHÒNG BAN + VỊ TRÍ)
    // ==========================================

    // Kiểm tra xem user có phải cấp quản lý chung (Tổng GĐ, GĐ, hoặc Trưởng Phòng) không
    public function isManagementLevel()
    {
        return $this->isGeneralDirector() || $this->isDirector() || $this->isDepartmentHead();
    }

    // Kiểm tra xem user có quyền duyệt các nghiệp vụ QA/COA đặc biệt không (Admin, Trưởng bộ phận QA hoặc nhân viên QA chính thức)
    public function canManageQA()
    {
        return $this->isITDepartment() || $this->isQADepartment() || $this->isDepartmentHead() || $this->isGeneralDirector();
    }
    // Kiểm tra quyền duyệt đơn mua hàng (Giám đốc Kinh doanh, Tổng GĐ hoặc IT)
    public function canApprovePurchaseOrder()
    {
        return $this->isSalesDirector() || $this->isGeneralDirector() || $this->isITDepartment();
    }

    // Kiểm tra quyền xác nhận nhà cung cấp đã giao hàng
    public function canConfirmSupplierDelivery()
    {
        return $this->isWarehouseManager() || $this->isITDepartment() || $this->isGeneralDirector();
    }
}