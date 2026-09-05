<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities\Notifications;

use openvk\Web\Models\Entities\User;
use openvk\Web\Util\IMBroker;

abstract class HybridNotification extends Notification
{
    public function emit(): bool
    {
        $broker = IMBroker::i();

        if (!$broker->isEnabled()) {
            return parent::emit();
        }

        $params = $this->getSendParams();
        $params["peer_id"] = $this->getRecipient()->getRealId(); 
        $params["random_id"] = (string) rand(1, 2147483647); 

        $response = $broker->invokeMethod($this->targetModel->getRealId(), $this->getSendMethod(), $params);
        if ($response === false) {
            return false;
        } else {
            $data = json_decode($response, true);

            if (isset($data['error'])) {
                bdump($data);
                return parent::emit();
            }

            return true;
        }
    }

    abstract public function getSendParams(): array;
    
    public function getSendMethod(): string
    {
        return "im.sendAction";
    }
}
