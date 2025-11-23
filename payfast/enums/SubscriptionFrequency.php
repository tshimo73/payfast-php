<?php 
namespace TshimologoMoeng\Payfast\Enums;

/**
 * Subscription Frequency
 */
enum SubscriptionFrequency: int
{
    case Daily = 1;
    case Weekly = 2;
    case Monthly = 3;
    case Quarterly = 4;
    case Biannually = 5;
    case Annually = 6;
};
