<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model;
class Broadcast extends Model { use HasFactory; protected $guarded=[]; protected function casts():array{return ['media'=>'array','keyboard'=>'array','scheduled_at'=>'datetime','started_at'=>'datetime','completed_at'=>'datetime','total_recipients'=>'integer','sent_count'=>'integer','failed_count'=>'integer'];} }
