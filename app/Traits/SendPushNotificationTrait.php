<?php 
namespace App\Traits;

use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;

trait SendPushNotificationTrait {

     /**
     * @return s3 image url
     */
    public function sendPushNotification($title,$body,array $playloadData,$deviceToken) {
            $optionBuilder = new OptionsBuilder();
            $optionBuilder->setTimeToLive(60*20);

            $notificationBuilder = new PayloadNotificationBuilder($title);
            $notificationBuilder->setBody($body)
                            ->setSound('default');

            $dataBuilder = new PayloadDataBuilder();
            $dataBuilder->addData($playloadData);

            $option = $optionBuilder->build();
            $notification = $notificationBuilder->build();
            $data = $dataBuilder->build();

            $token = $deviceToken;
            // $token = 'fPBd0WmfvUBBjpKZRsKLCg:APA91bGl9uthNVHhwtW92jpbPRTl6CAXaJ9W8I81AA3n1Z-phxUl6AqfGKvxpp3GgzrcNndXpfrZEKqgAix8JxRt0T0-EElP2mugrOCLvYQS_B0oCFNQC-YRKu2iIIkJtoJLL38oCeW8';

        $downstreamResponse = FCM::sendTo($token, $option, $notification, $data);
        return $downstreamResponse->numberSuccess();
        // return $downstreamResponse;
        // dd($downstreamResponse);
        // dd($downstreamResponse->numberSuccess());
        // dd($downstreamResponse->tokensToModify());
		
    }
}

