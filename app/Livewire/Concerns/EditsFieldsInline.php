<?php

namespace App\Livewire\Concerns;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

/**
 * Правка карточки прямо на месте, по одному полю (<x-ui.editable-field>): edit(поле), save(), cancel()
 * и черновик $value. Отдельной страницы правки нет. Допустимые поля — ключи editableRules().
 */
trait EditsFieldsInline
{
    /** Поле, которое сейчас правится (null — только просмотр). */
    #[Locked]
    public ?string $editing = null;

    /** Черновик значения правящегося поля. */
    public string $value = '';

    abstract protected function editableModel(): Model;

    /** Правила проверки: те же, что при создании (validationRules() модели). */
    abstract protected function editableRules(): array;

    protected function editableReadonly(): bool
    {
        return false;
    }

    /** Вызывается после сохранения поля (например, чтобы обновить историю на карточке). */
    protected function fieldSaved(): void {}

    public function edit(string $field): void
    {
        abort_if($this->editableReadonly(), 403);
        abort_unless(array_key_exists($field, $this->editableRules()), 404);

        // Щелчок по другому полю сначала сохраняет текущее; при ошибке проверки правка остаётся на нём.
        if ($this->editing !== null && $this->editing !== $field) {
            $this->save();
        }

        $current = $this->editableModel()->getAttribute($field);
        $this->value = match (true) {
            $current instanceof BackedEnum => $current->value,
            $current instanceof \DateTimeInterface => $current->format('Y-m-d'),
            default => (string) $current,
        };
        $this->editing = $field;
        $this->resetValidation();
    }

    public function save(): void
    {
        abort_if($this->editableReadonly(), 403);

        if ($this->editing === null) {
            return;
        }

        $field = $this->editing;

        // В сообщении — имя поля, как в форме.
        $this->validate(['value' => $this->editableRules()[$field]], [], ['value' => str_replace('_', ' ', $field)]);

        // Пустую строку храним как null; изменение попадает в историю (LogsActivity).
        $this->editableModel()->update([$field => $this->value === '' ? null : $this->value]);

        $this->cancel();
        $this->fieldSaved();
    }

    public function cancel(): void
    {
        $this->editing = null;
        $this->value = '';
        $this->resetValidation();
    }
}
