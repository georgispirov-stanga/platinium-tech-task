<?php

namespace App\Enum;

enum OrderStateEnum: string
{
	case CREATED = 'created';
	case PAID = 'paid';
	case CANCELLED = 'cancelled';
	case COMPLETED = 'completed';
}