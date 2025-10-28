<div>
    <x-action-section>
        <x-slot name="title">
            {{ __('نقل ملكية الفريق') }}
        </x-slot>

        <x-slot name="description">
            {{ __('نقل ملكية الفريق إلى مستخدم آخر.') }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-6">
                <div class="max-w-xl text-sm text-gray-600">
                    {{ __('اختر أحد المستخدمين لنقل ملكية الفريق إليه.') }}
                </div>

                <div class="mt-2">
                    <div class="col-span-6 sm:col-span-4">
                        <x-label for="user_select" value="{{ __('اختر المستخدم') }}" />

                        <!-- User Dropdown -->
                        <select id="user_select" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                wire:model="selectedUserId">
                            <option value="">{{ __('اختر مستخدم') }}</option>
                            @foreach ($this->allUsers as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-5">
                    <x-button wire:click="confirmTeamOwnershipTransfer" wire:loading.attr="disabled">
                        {{ __('نقل الملكية') }}
                    </x-button>
                </div>
            </div>

            <!-- Transfer Team Ownership Confirmation Modal -->
            <x-confirmation-modal wire:model.live="confirmingTeamOwnershipTransfer">
                <x-slot name="title">
                    {{ __('نقل ملكية الفريق') }}
                </x-slot>

                <x-slot name="content">
                    {{ __('هل أنت متأكد أنك تريد نقل ملكية هذا الفريق؟ بمجرد نقل الملكية، ستفقد امتيازات المالك.') }}
                </x-slot>

                <x-slot name="footer">
                    <x-secondary-button wire:click="$toggle('confirmingTeamOwnershipTransfer')" wire:loading.attr="disabled">
                        {{ __('إلغاء') }}
                    </x-secondary-button>

                    <x-danger-button class="ms-3" wire:click="transferOwnership" wire:loading.attr="disabled">
                        {{ __('نقل الملكية') }}
                    </x-danger-button>
                </x-slot>
            </x-confirmation-modal>
        </x-slot>
    </x-action-section>
</div>
