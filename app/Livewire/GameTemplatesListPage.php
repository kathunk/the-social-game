<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\GameTemplate;
use Thunk\Verbs\Facades\Verbs;
use Livewire\Attributes\Computed;
use App\Events\GameTemplateUnarchived;

class GameTemplatesListPage extends Component
{
    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    #[Computed]
    public function gameTemplates()
    {
        return GameTemplate::all();
    }

    #[Computed]
    public function archivedGameTemplates()
    {
        return GameTemplate::withoutGlobalScope('not_archived')
            ->where('is_archived', true)
            ->get();
    }

    public function mount()
    {
        if (! $this->user->is_super_admin) {
            return redirect()->route('dashboard');
        }
    }

    public function unarchiveTemplate(string $game_template_id)
    {
        GameTemplateUnarchived::fire(game_template_id: (int) $game_template_id);

        Verbs::commit();

        return redirect()->route('game-templates.show', $game_template_id);
    }

    public function render()
    {
        return view('livewire.game-templates-list-page');
    }
}
