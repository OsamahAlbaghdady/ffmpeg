<?php

use Carbon\Carbon;
use FFMpeg\Coordinate\TimeCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Filters\Video\VideoFilters;
use FFMpeg\Format\Video\WebM;
use FFMpeg\Format\Video\X264;
use Intervention\Image\Facades\Image;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
});




Route::post(
    'fmpeg',
    function (Request $request) {


        $ffmpeg = FFMpeg::create([
            'ffmpeg.binaries'  => exec('which ffmpeg'),
            'ffprobe.binaries' => exec('which ffprobe'),
            // 'ffmpeg.nvenc'     => true, // Enable NVIDIA GPU acceleration
            // 'ffmpeg.nvenc_device' => '/dev/nvidia0' // Specify the NVIDIA GPU device

        ]);
        $ffprobe = FFProbe::create([
            'ffmpeg.binaries'  => exec('which ffmpeg'),
            'ffprobe.binaries' => exec('which ffprobe'),
            // 'ffmpeg.nvenc'     => false, // Enable NVIDIA GPU acceleration
            // 'ffmpeg.nvenc_device' => '/dev/nvidia0' // Specify the NVIDIA GPU device

        ]);
        $video_extensions = ['mp4', 'mpeg', 'mpeg4', 'mov', 'webm', 'avi'];

        if ($request->files) {

            foreach ($request->file('files') as $file) {

                $thub =  'thubt' . Carbon::now()->timestamp . 'hj.jpg';
                $video = $ffmpeg->open($file);
                $video1 = $ffmpeg->open($file->getRealPath());
                $ff =  $video1->getPathfile();

                $video->frame(TimeCode::fromSeconds(2))->save('Attachments/thub/' . $thub);

                $destinationPath = public_path('Attachments/thub');

                $img = Image::make(public_path('Attachments/thub/') . $thub);

                $height = Image::make($img)->height();
                $width = Image::make($img)->width();


                $img->fit(360)->save($destinationPath . '/' . $thub);

                $name = 'ptest' . Carbon::now()->timestamp . 'v.mp4';

                $bitRate = $ffprobe->streams($file)->videos()->first()->get('bit_rate');


                // $format = new X264();


                // $format->setAdditionalParameters([
                //     '-vf',
                //     'zscale=t=linear:npl=100,format=gbrpf32le,zscale=p=bt709,tonemap=tonemap=hable:desat=0,zscale=t=bt709:m=bt709:r=tv,format=yuv420p',
                // ]);



                if ($bitRate > 500000) {
                    $bitRate = '500000';
                    // $format->setKiloBitrate(500);
                }

                if ($width > $height) {
                    if ($width < 854) {
                        $newWidth = $width;
                        $newHeight = $height;
                    } else {
                        $newWidth = 854;
                        $newHeight = ($height / $width) * 854;
                        if((int)$newHeight != 480)
                        $newHeight = 480;
                    }
                } else {
                    if ($height < 854) {
                        $newWidth = $width;
                        $newHeight = $height;
                    } else {
                        $newWidth = ($width / $height) * 854;
                        $newHeight = 854;
                    }
                }
                // $video->filters()
                //     ->resize(new \FFMpeg\Coordinate\Dimension((int)$newWidth, (int)$newHeight))
                //     ->synchronize();



                $savedPath =  base_path() . '/public/Attachments/' . $name;


                $resolution = (int)$newWidth . ":" . (int)$newHeight; // Set the desired output resolution here

		// $options = "-vf scale=$resolution -c:v h264_nvenc -cq 23 -b:v 500k -c:a copy";
                // $command = "/var/www/html/ffmpeg/ffmpeg -i $ff -c:v h264_nvenc -pix_fmt yuv420p -vf scale=$resolution -b:v $bitRate -c:a copy $savedPath";
                // $command = "ffmpeg -y -vsync 0 -hwaccel cuda -hwaccel_output_format cuda -i $ff -c:a copy -c:v h264_nvenc -preset p6 -tune ll -b:v $bitRate -bufsize 5M -maxrate 10M -qmin 0 -g 250 -bf 3 -b_ref_mode middle -temporal-aq 1 -rc-lookahead 20 -i_qfactor 0.75 -b_qfactor 1.1 $savedPath";

                $command = "ffmpeg -i $ff -c:v h264_nvenc -pix_fmt yuv420p -vf \"scale=$resolution,zscale=t=linear:npl=100,format=gbrpf32le,zscale=p=bt709,tonemap=tonemap=hable:desat=0,zscale=t=bt709:m=bt709:r=tv,format=yuv420p\" -b:v $bitRate -c:a copy $savedPath";
                echo exec($command);//

                // $video->save($format, base_path() . '/public/Attachments/' . $name);
                return response()->json(['url' => url('/Attachments/' . $name)]);
            }
        }

    }
);


