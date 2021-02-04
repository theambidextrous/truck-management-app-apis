<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Truck extends Model
{
    use HasFactory;
  
    protected $fillable = [
        'number',
        'owner',
        'make',
        'vin',
        'insurance_expires',
        'inspection_expires',
        'registration_expires',
        'is_active',
    ];

    public static function csvToArray($file = '', $delimiter = ',')
    {
        $filename = storage_path('app/cls/'.$file);

        if (!file_exists($filename) || !is_readable($filename))
            throw new \Exception('uploaded file was not found on the server');
    
        $header = null;
        $data = [];
        if (($handle = fopen($filename, 'r')) !== false)
        {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false)
            {
                if (!$header)
                    $header = $row;
                else
                    $data[] = array_combine($header, $row);
            }
            fclose($handle);
        }
    
        return $data;
    }    
}
