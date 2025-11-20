<?php
namespace Models;

class CompanyModel extends BaseModel
{
    protected $table = 'companies';
    protected $primaryKey = 'id';

    public function updateProfile(int $companyId, array $data)
    {
        return $this->where('id', $companyId)->update($data);
    }
}
