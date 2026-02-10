<?php

namespace Botble\RealEstate\Tables\Actions;

use Botble\Table\Actions\Action;

class AddToBoardAction extends Action
{
    public static function make(string $name = 'add-to-board'): static
    {
        return parent::make($name)
            ->label(trans('plugins/real-estate::board.add_to_board'))
            ->color('warning')
            ->icon('fas fa-clipboard-list')
            ->view('plugins/real-estate::boards.add-to-board-action')
            ->permission('board.edit');
    }
}
