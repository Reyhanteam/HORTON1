<?php
namespace App\Enums;
enum OrderStatus:string { case PENDING='pending'; case PAID='paid'; case PROCESSING='processing'; case COMPLETED='completed'; case CANCELLED='cancelled'; case FAILED='failed'; }
