<?php

namespace App\Enums;

enum Feature: string
{
    case Registration = 'registration';
    case PhoneVerification = 'phone_verification';
    case ChannelMembership = 'channel_membership';
    case Shop = 'shop';
    case Wallet = 'wallet';
    case Referral = 'referral';
    case Cashback = 'cashback';
    case Trial = 'trial';
    case Support = 'support';
    case Maintenance = 'maintenance';
}
