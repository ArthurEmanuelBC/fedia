<?php

namespace App\Http\Controllers;

use App\Jogador;
use App\Time;
use App\UserTime;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;

class ArtilhariaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $temporada  = $request->input('temporada');
        $competicao = $request->input('competicao');
        $time_id    = $request->input('time');

        $times_id = UserTime::where("era_id", Session::get('era')->id)
                            ->pluck('time_id')
                            ->toArray();

        $times     = arrayToSelect(Time::whereIn('id', $times_id)->get()->toArray(), 'id', 'nome');

        $jogadores = Jogador::join('gols', 'gols.jogador_id', '=', 'jogadors.id')
                            ->join('times', 'times.id', '=', 'jogadors.time_id')
                            ->select('jogadors.nome', 'jogadors.posicoes', 'jogadors.idade', 'jogadors.overall',
                                     \DB::raw('SUM(quantidade) as gols'), 'times.nome as nome_time')
                            ->whereIn('jogadors.time_id', $times_id)
                            ->groupBy('jogadors.id', 'times.nome')
                            ->orderBy('gols', 'DESC');

        if($competicao)
            $jogadores->where('gols.campeonato', $competicao);

        if($time_id)
            $jogadores->where('jogadors.time_id', $time_id);

        $jogadores = $jogadores->paginate(30);

        return view('artilharia.index', compact('times', 'jogadores'));
    }
}
