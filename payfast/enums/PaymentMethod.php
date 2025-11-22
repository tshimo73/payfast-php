<?php

namespace TshimologoMoeng\Payfast\Enums;

require './vendor/autoload.php';

enum PaymentMethod: string
{
    case EFT = 'ef';
    case Credit_Card = 'cc';
    case Debit_Card = 'dc';
    case Masterpass_Scan_to_Pay = 'mp';
    case Mobicred = 'mc';
    case SCode = 'sc';
    case SnapScan = 'ss';
    case Zapper = 'zp';
    case MoreTyme = 'mt';
    case Store_card = 'rc';
    case Mukuru = 'mu';
    case Apple_Pay = 'ap';
    case Samsung_Pay = 'sp';
    case Capitec_Pay = 'cp';
    case Google_Pay = 'gp';

    public function getDescription(): string
    {
        return match ($this) {
            self::EFT => 'Electronic Funds Transfer',
            self::Credit_Card => 'Credit Card',
            self::Debit_Card => 'Debit Card',
            self::Masterpass_Scan_to_Pay => 'Masterpass Scan to Pay',
            self::Mobicred => 'Mobicred',
            self::SCode => 'SCode',
            self::SnapScan => 'SnapScan',
            self::Zapper => 'Zapper',
            self::MoreTyme => 'MoreTyme',
            self::Store_card => 'Store Card',
            self::Mukuru => 'Mukuru',
            self::Apple_Pay => 'Apple Pay',
            self::Samsung_Pay => 'Samsung Pay',
            self::Capitec_Pay => 'Capitec Pay',
            self::Google_Pay => 'Google Pay'
        };
    }
}
