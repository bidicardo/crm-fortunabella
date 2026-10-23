<?php

namespace App\Livewire\Concerns;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Перенос карточек канбана (wire:sort Livewire 4) — общий для канбанов контрагентов и сделок: модель с
 * полями stage/position, scopeStage() и moveTo(). Компонент задаёт модель и справочник этапов и хранит
 * порядок колонок в $sort ('manual' — ручной порядок по position).
 */
trait MovesKanbanCards
{
    /** @return class-string<Model> */
    abstract protected function kanbanModel(): string;

    /** @return class-string<BackedEnum> */
    abstract protected function kanbanStages(): string;

    /** Вызывается после смены этапа карточки (любым способом); канбан сделок предупреждает о «Брони» без предоплаты. */
    protected function cardMoved(Model $card): void {}

    private function kanbanStage(mixed $stage): BackedEnum
    {
        return $this->kanbanStages()::tryFrom((string) $stage) ?? abort(404);
    }

    /** Перенос перетаскиванием: в другой этап и/или на другое место в колонке. */
    public function moveCard(mixed $id, mixed $position, mixed $stage): void
    {
        $stage = $this->kanbanStage($stage);
        $model = $this->kanbanModel();
        $card = $model::findOrFail((int) $id);

        // При сортировке не «Вручную» порядок задаёт сортировка: внутри колонки ничего не меняем,
        // в другую колонку — ставим в конец её ручного порядка.
        if ($this->sort !== 'manual') {
            if ($stage !== $card->stage) {
                $card->moveTo($stage);
                $this->cardMoved($card);
            }

            return;
        }

        $changed = $stage !== $card->stage;

        $ids = $model::stage($stage)->whereKeyNot($card->id)->orderBy('position')->orderBy('id')->pluck('id')->all();
        $position = max(0, min((int) $position, count($ids)));
        array_splice($ids, $position, 0, [$card->id]);

        DB::transaction(function () use ($card, $stage, $position, $ids) {
            // Смена этапа пишется в историю; position — служебное поле, в историю не попадает.
            $card->moveTo($stage, $position);

            // Порядок всей колонки — одним запросом: position = номер по порядку.
            $cases = str_repeat('when ? then ? ', count($ids));
            $in = implode(',', array_fill(0, count($ids), '?'));
            $bindings = collect($ids)->flatMap(fn ($id, $i) => [$id, $i])->merge($ids)->all();
            DB::update("update {$card->getTable()} set position = case id {$cases}end where id in ($in)", $bindings);
        });

        if ($changed) {
            $this->cardMoved($card);
        }
    }

    /**
     * Смена этапа без перетаскивания (список на карточке): в начало колонки — позиции колонки сдвигаются
     * на 1, карточка встаёт на 0 (position беззнаковая, отрицательной быть не может). toBase(): без
     * updated_at у сдвинутых карточек — position служебное поле.
     */
    public function changeStage(mixed $id, mixed $stage): void
    {
        $stage = $this->kanbanStage($stage);
        $model = $this->kanbanModel();
        $card = $model::findOrFail((int) $id);

        if ($stage !== $card->stage) {
            DB::transaction(function () use ($model, $card, $stage) {
                $model::stage($stage)->toBase()->increment('position');
                $card->moveTo($stage, 0);
            });
            $this->cardMoved($card);
        }
    }
}
