<template>
	<div class="ext-neowiki-layout-editor">
		<div class="ext-neowiki-layout-editor__metadata">
			<span class="ext-neowiki-layout-editor__metadata-item">
				<span class="ext-neowiki-layout-editor__metadata-label">
					{{ $i18n( 'neowiki-layout-editor-schema' ).text() }}
				</span>
				{{ initialLayout.getSchema() }}
			</span>
			<span class="ext-neowiki-layout-editor__metadata-item">
				<span class="ext-neowiki-layout-editor__metadata-label">
					{{ $i18n( 'neowiki-layout-editor-view-type' ).text() }}
				</span>
				{{ initialLayout.getType() }}
			</span>
		</div>

		<div class="ext-neowiki-layout-editor__description">
			<CdxField :optional="true">
				<template #label>
					{{ $i18n( 'neowiki-layout-editor-description' ).text() }}
				</template>
				<CdxTextArea
					:model-value="description"
					:placeholder="$i18n( 'neowiki-layout-editor-description-placeholder' ).text()"
					@update:model-value="onDescriptionChanged"
				/>
			</CdxField>
		</div>

		<div class="ext-neowiki-layout-editor__display-rules">
			<CdxToggleSwitch
				v-model="showAllProperties"
				@update:model-value="onShowAllToggled"
			>
				{{ $i18n( 'neowiki-layout-editor-show-all-properties' ).text() }}
			</CdxToggleSwitch>

			<CdxMessage
				v-if="schemaFetchFailed && !showAllProperties"
				type="error"
				:inline="true"
			>
				{{ $i18n( 'neowiki-layout-editor-schema-fetch-error' ).text() }}
			</CdxMessage>
			<DisplayRuleList
				v-else-if="!showAllProperties"
				:schema-properties="schemaProperties"
				:display-rules="currentDisplayRules"
				@update:display-rules="onDisplayRulesChanged"
			/>
		</div>
	</div>
</template>

<script setup lang="ts">
import { ref, shallowRef, watch } from 'vue';
import { Layout, type DisplayRule } from '@/domain/Layout.ts';
import type { PropertyDefinition } from '@/domain/PropertyDefinition.ts';
import { CdxField, CdxMessage, CdxTextArea, CdxToggleSwitch } from '@wikimedia/codex';
import DisplayRuleList from './DisplayRuleList.vue';
import { NeoWikiServices } from '@/NeoWikiServices.ts';

const props = defineProps<{
	initialLayout: Layout;
}>();

const emit = defineEmits<{
	change: [];
}>();

const schemaRepo = NeoWikiServices.getSchemaRepository();

const description = ref( props.initialLayout.getDescription() );
const showAllProperties = ref( props.initialLayout.getDisplayRules().length === 0 );
const currentDisplayRules = shallowRef<DisplayRule[]>( [ ...props.initialLayout.getDisplayRules() ] );
const savedDisplayRules = shallowRef<DisplayRule[]>( [ ...props.initialLayout.getDisplayRules() ] );
const schemaProperties = shallowRef<PropertyDefinition[]>( [] );
const schemaFetchFailed = ref( false );

async function fetchSchemaProperties( schemaName: string ): Promise<void> {
	schemaFetchFailed.value = false;
	try {
		const schema = await schemaRepo.getSchema( schemaName );
		schemaProperties.value = [ ...schema.getPropertyDefinitions() ];
	} catch ( error ) {
		console.error( 'Failed to fetch schema:', error );
		schemaFetchFailed.value = true;
	}
}

watch( () => props.initialLayout, ( layout ) => {
	description.value = layout.getDescription();
	showAllProperties.value = layout.getDisplayRules().length === 0;
	currentDisplayRules.value = [ ...layout.getDisplayRules() ];
	savedDisplayRules.value = [ ...layout.getDisplayRules() ];
	fetchSchemaProperties( layout.getSchema() );
}, { immediate: true } );

function onDescriptionChanged( value: string ): void {
	description.value = value;
	emit( 'change' );
}

function onShowAllToggled(): void {
	if ( showAllProperties.value ) {
		savedDisplayRules.value = [ ...currentDisplayRules.value ];
		currentDisplayRules.value = [];
	} else {
		currentDisplayRules.value = [ ...savedDisplayRules.value ];
	}
	emit( 'change' );
}

function onDisplayRulesChanged( rules: DisplayRule[] ): void {
	currentDisplayRules.value = rules;
	emit( 'change' );
}

export interface LayoutEditorExposes {
	getLayout: () => Layout;
}

defineExpose( {
	getLayout: function(): Layout {
		return new Layout(
			props.initialLayout.getName(),
			props.initialLayout.getSchema(),
			props.initialLayout.getType(),
			description.value,
			showAllProperties.value ? [] : currentDisplayRules.value,
			props.initialLayout.getSettings()
		);
	}
} );
</script>

<style lang="less">
@import ( reference ) '@wikimedia/codex-design-tokens/theme-wikimedia-ui.less';

.ext-neowiki-layout-editor {
	padding: @spacing-100;

	@media ( min-width: @min-width-breakpoint-desktop ) {
		padding: @spacing-150;
	}

	&__description {
		margin-block-end: @spacing-100;
	}

	&__metadata {
		display: flex;
		gap: @spacing-200;
		margin-block-end: @spacing-100;
	}

	&__metadata-item {
		display: inline-flex;
		gap: @spacing-50;
	}

	&__metadata-label {
		color: @color-subtle;
	}

	&__display-rules {
		border-block-start: @border-subtle;
		padding-block-start: @spacing-100;
	}
}
</style>
