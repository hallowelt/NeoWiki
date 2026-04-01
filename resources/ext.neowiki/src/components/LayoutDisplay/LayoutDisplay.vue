<template>
	<div class="ext-neowiki-layout-display">
		<LayoutDisplayHeader
			:layout="currentLayout"
			:can-edit-layout="canEditLayout"
			@edit="isEditorOpen = true"
		/>

		<div class="ext-neowiki-layout-display__metadata">
			<div class="ext-neowiki-layout-display__metadata-item">
				<span class="ext-neowiki-layout-display__metadata-label">
					{{ $i18n( 'neowiki-layout-display-schema' ).text() }}
				</span>
				<a :href="schemaPageUrl">{{ currentLayout.getSchema() }}</a>
			</div>
			<div class="ext-neowiki-layout-display__metadata-item">
				<span class="ext-neowiki-layout-display__metadata-label">
					{{ $i18n( 'neowiki-layout-display-view-type' ).text() }}
				</span>
				<span>{{ currentLayout.getType() }}</span>
			</div>
		</div>

		<CdxTable
			v-if="hasDisplayRules"
			:columns="columns"
			:data="displayRuleRows"
			:caption="$i18n( 'neowiki-layout-display-rules-caption' ).text()"
			:hide-caption="true"
		/>
		<p
			v-else
			class="ext-neowiki-layout-display__no-rules"
		>
			{{ $i18n( 'neowiki-layout-display-no-rules' ).text() }}
		</p>
	</div>
</template>

<script setup lang="ts">
import { computed, shallowRef, watch } from 'vue';
import { Layout } from '@/domain/Layout.ts';
import { CdxTable } from '@wikimedia/codex';
import type { TableColumn } from '@wikimedia/codex';
import LayoutDisplayHeader from './LayoutDisplayHeader.vue';
import { useLayoutPermissions } from '@/composables/useLayoutPermissions.ts';

const props = defineProps( {
	layout: {
		type: Layout,
		required: true,
	},
} );

const isEditorOpen = shallowRef( false );
const currentLayout = shallowRef<Layout>( props.layout );
const { canEditLayout, checkEditPermission } = useLayoutPermissions();

watch( () => props.layout, ( newLayout ) => {
	currentLayout.value = newLayout;
	checkEditPermission( newLayout.getName() );
}, { immediate: true } );

const schemaPageUrl = computed( () => {
	return mw.util.getUrl( `Schema:${ currentLayout.value.getSchema() }` );
} );

const hasDisplayRules = computed( () => currentLayout.value.getDisplayRules().length > 0 );

const columns = computed<TableColumn[]>( () => [
	{
		id: 'property',
		label: mw.msg( 'neowiki-layout-display-rule-property' ),
	},
	{
		id: 'displayAttributes',
		label: mw.msg( 'neowiki-layout-display-rule-attributes' ),
	},
] );

const displayRuleRows = computed( () =>
	currentLayout.value.getDisplayRules().map( ( rule ) => ( {
		property: rule.property.toString(),
		displayAttributes: rule.displayAttributes && Object.keys( rule.displayAttributes ).length > 0
			? JSON.stringify( rule.displayAttributes )
			: '-',
	} ) ),
);
</script>

<style lang="less">
@import ( reference ) '@wikimedia/codex-design-tokens/theme-wikimedia-ui.less';

.ext-neowiki-layout-display {
	max-width: 64rem;

	&__metadata {
		display: flex;
		gap: @spacing-200;
		margin-block: @spacing-100;
	}

	&__metadata-item {
		display: flex;
		gap: @spacing-50;
	}

	&__metadata-label {
		font-weight: @font-weight-bold;
	}

	&__no-rules {
		color: @color-subtle;
	}
}
</style>
