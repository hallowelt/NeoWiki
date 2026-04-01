<template>
	<div class="ext-neowiki-layout-creator">
		<CdxField
			:status="nameStatus"
			:messages="nameError ? { error: nameError } : {}"
		>
			<CdxTextInput
				ref="nameInputRef"
				v-model="layoutName"
				:placeholder="$i18n( 'neowiki-layout-creator-name-placeholder' ).text()"
				@input="onNameInput"
			/>
			<template #label>
				{{ $i18n( 'neowiki-layout-creator-name-field' ).text() }}
			</template>
		</CdxField>

		<CdxField>
			<CdxSelect
				v-model:selected="selectedSchema"
				:menu-items="schemaMenuItems"
				:default-label="$i18n( 'neowiki-layout-creator-schema-placeholder' ).text()"
				@update:selected="onChange"
			/>
			<template #label>
				{{ $i18n( 'neowiki-layout-creator-schema-field' ).text() }}
			</template>
		</CdxField>

		<CdxField>
			<CdxSelect
				v-model:selected="selectedViewType"
				:menu-items="viewTypeMenuItems"
				:default-label="$i18n( 'neowiki-layout-creator-view-type-placeholder' ).text()"
				@update:selected="onChange"
			/>
			<template #label>
				{{ $i18n( 'neowiki-layout-creator-view-type-field' ).text() }}
			</template>
		</CdxField>
	</div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { CdxField, CdxTextInput, CdxSelect } from '@wikimedia/codex';
import type { MenuItemData, ValidationStatusType } from '@wikimedia/codex';
import { NeoWikiExtension } from '@/NeoWikiExtension.ts';
import { NeoWikiServices } from '@/NeoWikiServices.ts';
import { Layout } from '@/domain/Layout.ts';
import { useLayoutStore } from '@/stores/LayoutStore.ts';

const emit = defineEmits<{
	change: [];
}>();

const layoutStore = useLayoutStore();

const DEBOUNCE_DELAY = 300;

const layoutName = ref( '' );
const nameError = ref( '' );
const nameStatus = ref<ValidationStatusType>( 'default' );
const nameInputRef = ref<InstanceType<typeof CdxTextInput> | null>( null );
const selectedSchema = ref<string | null>( null );
const selectedViewType = ref<string | null>( null );
const schemaNames = ref<string[]>( [] );
let debounceTimer: ReturnType<typeof setTimeout> | null = null;
let requestSequence = 0;

const schemaMenuItems = computed<MenuItemData[]>( () =>
	schemaNames.value.map( ( name ) => ( { label: name, value: name } ) )
);

const viewTypeMenuItems = computed<MenuItemData[]>( () =>
	NeoWikiServices.getViewTypeRegistry().getTypeNames().map(
		( name ) => ( { label: name, value: name } )
	)
);

onMounted( async () => {
	try {
		const repo = NeoWikiExtension.getInstance().getSchemaRepository();
		schemaNames.value = await repo.getSchemaNames( '' );
	} catch ( error ) {
		console.error( 'Failed to fetch schema names:', error );
	}
} );

function onChange(): void {
	emit( 'change' );
}

function onNameInput(): void {
	nameError.value = '';
	nameStatus.value = 'default';
	emit( 'change' );
	clearDebounceTimer();
	requestSequence++;

	const name = layoutName.value.trim();

	if ( !name ) {
		nameError.value = mw.msg( 'neowiki-layout-creator-name-required' );
		nameStatus.value = 'error';
		return;
	}

	const expectedSequence = requestSequence;
	debounceTimer = setTimeout( () => checkDuplicateName( name, expectedSequence ), DEBOUNCE_DELAY );
}

async function checkDuplicateName( name: string, expectedSequence: number ): Promise<void> {
	try {
		await layoutStore.getOrFetchLayout( name );

		if ( expectedSequence !== requestSequence ) {
			return;
		}

		nameError.value = mw.msg( 'neowiki-layout-creator-name-taken' );
		nameStatus.value = 'error';
	} catch {
		// Layout not found — name is available
	}
}

function clearDebounceTimer(): void {
	if ( debounceTimer !== null ) {
		clearTimeout( debounceTimer );
		debounceTimer = null;
	}
}

async function validate(): Promise<boolean> {
	clearDebounceTimer();
	requestSequence++;

	const name = layoutName.value.trim();

	if ( !name ) {
		nameError.value = mw.msg( 'neowiki-layout-creator-name-required' );
		nameStatus.value = 'error';
		return false;
	}

	try {
		await layoutStore.getOrFetchLayout( name );
		nameError.value = mw.msg( 'neowiki-layout-creator-name-taken' );
		nameStatus.value = 'error';
		return false;
	} catch {
		// Layout not found — name is available
	}

	if ( !selectedSchema.value ) {
		return false;
	}

	if ( !selectedViewType.value ) {
		return false;
	}

	return true;
}

function getLayout(): Layout | null {
	const name = layoutName.value.trim();

	if ( !name || !selectedSchema.value || !selectedViewType.value ) {
		return null;
	}

	return new Layout( name, selectedSchema.value, selectedViewType.value, '', [], {} );
}

function reset(): void {
	clearDebounceTimer();
	requestSequence++;
	layoutName.value = '';
	nameError.value = '';
	nameStatus.value = 'default';
	selectedSchema.value = null;
	selectedViewType.value = null;
}

export interface LayoutCreatorExposes {
	validate: () => Promise<boolean>;
	getLayout: () => Layout | null;
	reset: () => void;
}

defineExpose( { validate, getLayout, reset } );
</script>

<style lang="less">
@import ( reference ) '@wikimedia/codex-design-tokens/theme-wikimedia-ui.less';

.ext-neowiki-layout-creator {
	display: flex;
	flex-direction: column;
	gap: @spacing-100;
	padding: @spacing-100;

	@media ( min-width: @min-width-breakpoint-desktop ) {
		padding: @spacing-150;
	}
}
</style>
