<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ServiceInstruction extends Model
{
    use HasFactory;

	protected $fillable = [
		'service_id',
		'instructions',
		'type',
		'notes',
	];

	private static ?string $instructionColumn = null;

	public function getInstructionAttribute($value)
	{
		if (!is_null($value)) {
			return $value;
		}

		return $this->attributes['instructions'] ?? null;
	}

	public function setInstructionAttribute($value): void
	{
		$column = $this->resolveInstructionColumn();
		$this->attributes[$column] = $value;
	}

	private function resolveInstructionColumn(): string
	{
		if (self::$instructionColumn !== null) {
			return self::$instructionColumn;
		}

		self::$instructionColumn = Schema::hasColumn($this->getTable(), 'instruction')
			? 'instruction'
			: 'instructions';

		return self::$instructionColumn;
	}

	public function service()
	{
		return $this->belongsTo(Service::class);
	}

}
