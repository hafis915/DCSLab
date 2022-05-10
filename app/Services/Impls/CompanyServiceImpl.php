<?php

namespace App\Services\Impls;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Actions\RandomGenerator;
use App\Services\CompanyService;

use App\Models\User;
use App\Models\Company;
use finfo;
use Illuminate\Support\Facades\Cache;

class CompanyServiceImpl implements CompanyService
{
    public function __construct()
    {
        
    }
    
    public function create(
        string $code, 
        string $name, 
        ?string $address, 
        bool $default, 
        int $status, 
        int $userId
    ): Company
    {
        DB::beginTransaction();
        $timer_start = microtime(true);

        try {
            $usr = User::find($userId);
            if (!$usr) return null;

            if ($usr->companies()->count() == 0) {
                $default = true;
                $status = 1;
            }

            if ($code == Config::get('const.DEFAULT.KEYWORDS.AUTO')) {
                $generatedCode = '';
                do {
                    $generatedCode = $this->generateUniqueCode();

                } while (!$this->isUniqueCode($generatedCode, $userId));

                $code = $generatedCode;
            }

            $company = new Company();
            $company->code = $code;
            $company->name = $name;
            $company->address = $address;
            $company->default = $default;
            $company->status = $status;

            $company->save();

            $usr->companies()->attach([$company->id]);

            DB::commit();

            $this->flushCache();

            return $company;
        } catch (Exception $e) {
            DB::rollBack();
            Log::debug('['.session()->getId().'-'.(is_null(auth()->user()) ? '':auth()->user()->id).'] '.__METHOD__.$e);
            return Config::get('const.ERROR_RETURN_VALUE');
        } finally {
            $execution_time = microtime(true) - $timer_start;
            Log::channel('perfs')->info('['.session()->getId().'-'.(is_null(auth()->user()) ? '':auth()->user()->id).'] '.__METHOD__.' ('.number_format($execution_time, 1).'s)');
        }
    }

    public function read(int $userId, string $search = '', bool $paginate = true, int $page, int $perPage = 10, bool $useCache = true)
    {
        $timer_start = microtime(true);

        try {
            $cacheKey = '';
            if ($useCache) {
                $cacheKey = 'read_'.$search.'-'.$paginate.'-'.$page.'-'.$perPage;
                $cacheResult = $this->readFromCache($cacheKey);

                if (!is_null($cacheResult)) return $cacheResult;
            }

            $result = null;

            $usr = User::find($userId);
            if (!$usr) return null;
    
            $compIds = $usr->companies()->pluck('company_id');
            
            if (empty($search)) {
                $companies = Company::whereIn('id', $compIds)->latest();
            } else {
                $companies = Company::whereIn('id', $compIds)->where('name', 'like', '%'.$search.'%')->latest();
            }
    
            if ($paginate) {
                $perPage = is_numeric($perPage) ? $perPage : Config::get('const.DEFAULT.PAGINATION_LIMIT');
                $result = $companies->paginate($perPage);
            } else {
                $result = $companies->get();
            }

            if ($useCache) $this->saveToCache($cacheKey, $result);
            
            return $result;
        } catch (Exception $e) {
            Log::debug('['.session()->getId().'-'.(is_null(auth()->user()) ? '':auth()->user()->id).'] '.__METHOD__.$e);
            return Config::get('const.DEFAULT.ERROR_RETURN_VALUE');
        } finally {
            $execution_time = microtime(true) - $timer_start;
            Log::channel('perfs')->info('['.session()->getId().'-'.(is_null(auth()->user()) ? '':auth()->user()->id).'] '.__METHOD__.' ('.number_format($execution_time, 1).'s)');
        }
    }

    private function readFromCache($key)
    {
        try {
            if (!Config::get('const.DEFAULT.DATA_CACHE.ENABLED')) return Config::get('const.DEFAULT.ERROR_RETURN_VALUE');

            if (!Cache::has($key)) return Config::get('const.DEFAULT.ERROR_RETURN_VALUE');

            return Cache::get($key);
        } catch (Exception $e) {
            Log::debug('['.session()->getId().'-'.(is_null(auth()->user()) ? '':auth()->user()->id).'] '.__METHOD__.$e);
            return Config::get('const.DEFAULT.ERROR_RETURN_VALUE');
        } finally {
            Log::channel('cachehits')->info('['.session()->getId().'-'.(is_null(auth()->user()) ? '':auth()->user()->id).'] '.__METHOD__.' Read Key: '.$key);
        }
    }

    private function saveToCache($key, $val)
    {
        try {
            if (empty($key)) return;

            Cache::tags([auth()->user()->id, __METHOD__])->add($key, $val, Config::get('const.DEFAULT.DATA_CACHE.CACHE_TIME.ENV'));
        } catch (Exception $e) {
            Log::debug('['.session()->getId().'-'.(is_null(auth()->user()) ? '':auth()->user()->id).'] '.__METHOD__.$e);
        } finally {
            Log::channel('cachehits')->info('['.session()->getId().'-'.(is_null(auth()->user()) ? '':auth()->user()->id).'] '.__METHOD__.' Save Key: '.$key);
        }
    }

    private function flushCache()
    {
        try {
            Cache::tags([auth()->user()->id, __METHOD__])->flush();
        } catch (Exception $e) {
            Log::debug('['.session()->getId().'-'.(is_null(auth()->user()) ? '':auth()->user()->id).'] '.__METHOD__.$e);
        } finally {
            Log::channel('cachehits')->info('['.session()->getId().'-'.(is_null(auth()->user()) ? '':auth()->user()->id).'] '.__METHOD__.' Cache Flushed for tags: '.(is_null(auth()->user()) ? '':auth()->user()->id).', '.__METHOD__);
        }
    }

    public function getAllActiveCompany(int $userId)
    {
        $timer_start = microtime(true);

        try {
            $usr = User::find($userId);
            if (!$usr) return null;
    
            $compIds = $usr->companies()->pluck('company_id');
            return Company::where('status', '=', 1)->whereIn('id',  $compIds)->get();    
        } catch (Exception $e) {
            return Config::get('const.DEFAULT.ERROR_RETURN_VALUE');
        } finally {
            $execution_time = microtime(true) - $timer_start;
            Log::channel('perfs')->info('['.session()->getId().'-'.(is_null(auth()->user()) ? '':auth()->user()->id).'] '.__METHOD__.' ('.number_format($execution_time, 1).'s)');
        }
    }

    public function update(
        int $id, 
        string $code, 
        string $name, 
        ?string $address, 
        bool $default, 
        int $status
    ): Company
    {
        DB::beginTransaction();
        $timer_start = microtime(true);

        try {
            $company = Company::find($id);

            $company->update([
                'code' => $code,
                'name' => $name,
                'address' => $address,
                'default' => $default,
                'status' => $status
            ]);

            DB::commit();

            $this->flushCache();

            return $company->refresh();
        } catch (Exception $e) {
            DB::rollBack();
            Log::debug('['.session()->getId().'-'.(is_null(auth()->user()) ? '':auth()->user()->id).'] '.__METHOD__.$e);
            return Config::get('const.ERROR_RETURN_VALUE');
        } finally {
            $execution_time = microtime(true) - $timer_start;
            Log::channel('perfs')->info('['.session()->getId().'-'.(is_null(auth()->user()) ? '':auth()->user()->id).'] '.__METHOD__.' ('.number_format($execution_time, 1).'s)');
        }
    }

    public function delete(int $userId, int $id): bool
    {
        DB::beginTransaction();
        $timer_start = microtime(true);

        $retval = false;
        try {
            $company = Company::find($id);

            if ($company) {
                $usr = User::find($userId);
                $usr->companies()->detach([$company->id]);
    
                $retval = $company->delete();    
            }

            DB::commit();

            $this->flushCache();

            return $retval;
        } catch (Exception $e) {
            DB::rollBack();
            Log::debug('['.session()->getId().'-'.(is_null(auth()->user()) ? '':auth()->user()->id).'] '.__METHOD__.$e);
            return Config::get('const.ERROR_RETURN_VALUE');
        } finally {
            $execution_time = microtime(true) - $timer_start;
            Log::channel('perfs')->info('['.session()->getId().'-'.(is_null(auth()->user()) ? '':auth()->user()->id).'] '.__METHOD__.' ('.number_format($execution_time, 1).'s)');
        }
    }

    public function generateUniqueCode(): string
    {
        $rand = new RandomGenerator();
        $code = $rand->generateAlphaNumeric(3).$rand->generateFixedLengthNumber(3);
        return $code;
    }

    public function isUniqueCode(string $code, int $userId, ?int $exceptId = null): bool
    {
        $user = User::find($userId);

        if ($user->companies->count() == 0) return true;

        $result = $user->companies()->where('code', '=' , $code);

        if($exceptId)
            $result = $result->get()->where('id', '<>', $exceptId);

        return $result->count() == 0 ? true:false;
    }

    public function isDefaultCompany(int $companyId): bool
    {
        return Company::where('id', '=', $companyId)->first()->default;
    }

    public function resetDefaultCompany(int $userId): bool
    {
        DB::beginTransaction();
        $timer_start = microtime(true);

        try {
            $usr = User::find($userId);
            $compIds = $usr->companies()->pluck('company_id');

            $retval = Company::whereIn('id', $compIds)
                      ->update(['default' => 0]);

            DB::commit();

            return $retval;
        } catch (Exception $e) {
            DB::rollBack();
            Log::debug('['.session()->getId().'-'.(is_null(auth()->user()) ? '':auth()->user()->id).'] '.__METHOD__.$e);
            return Config::get('const.ERROR_RETURN_VALUE');
        } finally {
            $execution_time = microtime(true) - $timer_start;
            Log::channel('perfs')->info('['.session()->getId().'-'.(is_null(auth()->user()) ? '':auth()->user()->id).'] '.__METHOD__.' ('.number_format($execution_time, 1).'s)');
        }
    }

    public function getCompanyById(int $companyId): Company
    {
        return Company::find($companyId)->first();
    }

    public function getDefaultCompany(int $userId): Company
    {
        $usr = User::find($userId);
        return $usr->companies()->where('default','=', 1)->first();
    }
}
