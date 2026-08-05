<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class InvoicingInfo extends Model
{
    protected $guarded = [];

    protected $casts = [
        'plan_start_date' => 'datetime',
        'certificate_expiration_date' => 'datetime',
        'has_web_app' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'dias_transcurridos',
        'folios_usados',
        'promedio_folios_usados_por_dia',
        'dias_estimados_para_terminar',
        'dias_restantes_certificado'
    ];

    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getDiasTranscurridosAttribute()
    {
        if (!$this->plan_start_date) return 0;
        return max(1, $this->plan_start_date->diffInDays(now()));
    }

    public function getFoliosUsadosAttribute()
    {
        return max(0, $this->folios_total - $this->folios_remaining);
    }

    public function getPromedioFoliosUsadosPorDiaAttribute()
    {
        $dias = $this->dias_transcurridos;
        if ($dias == 0) return 0;
        return round($this->folios_usados / $dias, 2);
    }

    public function getDiasEstimadosParaTerminarAttribute()
    {
        $promedio = $this->promedio_folios_usados_por_dia;
        if ($promedio <= 0) return 999999; // Essentially infinite if no usage
        return (int) round($this->folios_remaining / $promedio);
    }

    public function getDiasRestantesCertificadoAttribute()
    {
        if (!$this->certificate_expiration_date) return null;
        return (int) round(now()->startOfDay()->diffInDays($this->certificate_expiration_date, false));
    }
}

