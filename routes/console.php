<?php

use App\Models\City;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('cities:import-ibge', function () {
    $this->info('Baixando cidades do IBGE...');

    $response = Http::timeout(30)
        ->retry(3, 500)
        ->get('https://servicodados.ibge.gov.br/api/v1/localidades/municipios');

    if (! $response->ok()) {
        $this->error('Falha ao acessar a API do IBGE.');
        return 1;
    }

    $data = $response->json();
    if (! is_array($data)) {
        $this->error('Resposta inesperada da API do IBGE.');
        return 1;
    }

    City::query()->delete();

    $payload = [];
    $inserted = 0;

    foreach ($data as $row) {
        $name = $row['nome'] ?? null;
        $stateName = data_get($row, 'microrregiao.mesorregiao.UF.nome');
        $stateCode = data_get($row, 'microrregiao.mesorregiao.UF.sigla');

        if (! $name || ! $stateName) {
            continue;
        }

        $payload[] = [
            'name' => $name,
            'state' => $stateName,
            'state_code' => $stateCode,
        ];

        if (count($payload) >= 500) {
            City::query()->insert($payload);
            $inserted += count($payload);
            $payload = [];
        }
    }

    if ($payload) {
        City::query()->insert($payload);
        $inserted += count($payload);
    }

    $this->info("Importacao concluida. Cidades inseridas: {$inserted}.");

    return 0;
})->purpose('Importa todas as cidades do Brasil usando a API do IBGE.');
