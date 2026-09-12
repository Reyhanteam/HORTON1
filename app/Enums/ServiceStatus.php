<?php
namespace App\Enums;
enum ServiceStatus:string { case PENDING='pending'; case ACTIVE='active'; case SUSPENDED='suspended'; case EXPIRED='expired'; case DISABLED='disabled'; case FAILED='failed'; }
