<?php

namespace App\Support;

/**
 * Marker interface for per-game-mode form element providers.
 *
 * Any class under app/Support/FormBuilderElements/ implementing this
 * interface is auto-discovered by FormElementRegistry, and its public
 * methods become callable on FormBuilder — no registration required.
 *
 * Provider methods receive the FormBuilder as their first parameter and
 * append elements via $form->addElement([...]):
 *
 *     public function myBoard(FormBuilder $form, Player $player): void
 *     {
 *         $form->addElement(['type' => 'my_board', ...]);
 *     }
 *
 * Challenges then call $this->form()->myBoard(player: $player) exactly as
 * if the method lived on FormBuilder itself.
 */
interface FormElementProvider {}
