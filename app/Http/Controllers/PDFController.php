<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use ConvertApi\ConvertApi;
use Illuminate\Http\Request;
use Illuminate\Log\Logger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;


class PDFController extends Controller
{
    public function view(Request $request)
    {
        $param = [
            'url' => 'https://api.tracuuthansohoconline.com/api/user/look-up/f20800d5-353d-4960-9549-8c7e4c0d49b4',
            'token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VySWQiOjYsInJvbGUiOiJBRE1JTiIsImlhdCI6MTY5ODQyNjkxMSwiZXhwIjoxNzAxMDE4OTExfQ.2104C_aMaf-OniN2wXUZFoVsetB1dczV4uU-bBnndU8'
        ];
        $data = $this->getData($param);
        
        // $options = new Options();
        // $options->set('isHtml5ParserEnabled', true);
        // $dompdf = new Dompdf($options);
        return view('welcome', ['data' => $data]);
        // $html = view('welcome', ['data' => $data])->render();

        // $dompdf->loadHtml($html);

        // $dompdf->setPaper('A4', 'portrait');
        // $dompdf->render();

        // return $dompdf->stream('document.pdf');
    }

    public function down(Request $request)
    {
        $param = [
            'url' => 'https://api.tracuuthansohoconline.com/api/user/look-up/f20800d5-353d-4960-9549-8c7e4c0d49b4',
            'token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VySWQiOjYsInJvbGUiOiJBRE1JTiIsImlhdCI6MTY5ODQyNjkxMSwiZXhwIjoxNzAxMDE4OTExfQ.2104C_aMaf-OniN2wXUZFoVsetB1dczV4uU-bBnndU8'
        ];
        $data = $this->getData($param);
        
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $html = view('welcome', ['data' => $data])->render();

        $dompdf->loadHtml($html);

        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->stream('document.pdf');
    }

    public function pdf(Request $request)
    {
        $param = [
            'url' => 'https://api.tracuuthansohoconline.com/api/user/look-up/f20800d5-353d-4960-9549-8c7e4c0d49b4',
            'token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VySWQiOjYsInJvbGUiOiJBRE1JTiIsImlhdCI6MTY5ODQyNjkxMSwiZXhwIjoxNzAxMDE4OTExfQ.2104C_aMaf-OniN2wXUZFoVsetB1dczV4uU-bBnndU8'
        ];
        $data = $this->getData($param);
        $name = $data['id'] . '-' . $data['dateSearch'] . '.html';
        $namePDF = $data['id'] . '-' . $data['dateSearch'] . '.pdf';
        if (!file_exists(public_path() . '/html/')) {
            mkdir(public_path() . '/html/', 0777, true);
        }
        if (!file_exists(public_path() . '/pdf/')) {
            mkdir(public_path() . '/pdf/', 0777, true);
        }
        Process::run('chmod -R 777 ' . public_path());
        $pathHtml = public_path() . '/html/' . $name;
        $pathPDF = public_path() . '/pdf/' . $data['id'] . '-' . $data['dateSearch'] . '.pdf';
        if (!file_exists($pathPDF)) {
            $file = fopen($pathHtml, 'w+');
            $htmlStr = view('welcome', ['data' => $data])->render();
            fwrite($file, $htmlStr);
            try {
                $processName = "wkhtmltopdf $pathHtml $pathPDF";
                Process::run($processName);
                Log::info('process', ['process' => $processName]);
            } catch (\Exception $exception) {
                throw $exception;
            }
        }
        if (File::exists($pathPDF)) {
            $headers = ['Content-Type' => 'application/pdf'];
            $response = Response::download($pathPDF, $namePDF, $headers);
    
            return $response;
        } else {
            // Handle the case where the PDF file does not exist.
            return response()->json(['error' => 'PDF file not found']);
        }
    }

    public function renderPDF(Request $request)
    {
        $data = $this->getData($request->all());
        $name = $data['id'] . '-' . $data['dateSearch'] . '.html';
        if (!Storage::exists($name)) {
            Storage::put($name, view('welcome', ['data' => $data])->render());
        }
        $pathPDF = storage_path() . '/app/' . $name;
        $pathPublic = storage_path() . '/app/public/' . $name . '.pdf';
        $result = Process::run('wkhtmltopdf ' . $pathPDF . ' ' . $pathPublic);
        return response()->json(['path' => asset($name)]);
    }

    public function getData($data)
    {
        $token = $data['token'];
        $url = $data['url'];
        $callAPI = Http::withHeaders([
            'Authorization' => "Bearer $token"
        ])->get($url);
        $data = $callAPI->json();
        $title = $data['data']['data'];
        $count = 1;
        foreach ($title as $key => $item) {
            if (isset($item['title'])) {
                if ($count <= 6) {
                    $data['data']['data'][$key]['page'] = 2;
                }
                if ($count > 6 && $count <= 13) {
                    $data['data']['data'][$key]['page'] = 3;
                }
                if ($count > 13) {
                    $data['data']['data'][$key]['page'] = 4;
                }
            }
            $count++;
        }
        $data['data']['dateOfBirth'] = Carbon::create($data['data']['dateOfBirth'])->format('d/m/Y');
        return $data['data'];
    }
}
