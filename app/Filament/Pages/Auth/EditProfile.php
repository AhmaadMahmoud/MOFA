<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

/**
 * Faten's account page: who she is at the top, then two cards (account, password),
 * each with its title on top and the fields side by side. Narrow, like a settings page.
 */
class EditProfile extends BaseEditProfile
{
    protected Width|string|null $maxContentWidth = Width::FourExtraLarge;

    public function getSubheading(): ?string
    {
        return __('Your sign-in details for the dashboard.');
    }

    public function form(Schema $schema): Schema
    {
        // Labels above the fields: the section titles already sit beside them.
        return $schema->inlineLabel(false)->components([
            View::make('filament.profile.header'),

            Section::make(__('Account'))
                ->description(__('Your name shows in the dashboard. You sign in with this email.'))
                ->icon(Heroicon::OutlinedUserCircle)
                ->columns(['md' => 2])
                ->schema([
                    $this->getNameFormComponent()->autofocus(false),
                    $this->getEmailFormComponent()->extraInputAttributes(['dir' => 'ltr']),
                ]),

            Section::make(__('Password'))
                ->description(__('Choose a new password with at least 8 characters. To save it, you confirm your current password.'))
                ->icon(Heroicon::OutlinedLockClosed)
                ->columns(['md' => 2])
                ->schema([
                    $this->getPasswordFormComponent()->extraInputAttributes(['dir' => 'ltr']),
                    $this->getPasswordConfirmationFormComponent()->extraInputAttributes(['dir' => 'ltr']),
                    $this->getCurrentPasswordFormComponent()->extraInputAttributes(['dir' => 'ltr'])->columnSpanFull(),
                ]),
        ]);
    }
}
