<template>
	<div class="ext-neowiki-layouts-page">
		<CdxTable
			:columns="columns"
			:data="rows"
			:caption="$i18n( 'neowiki-special-layouts' ).text()"
			:pending="loading"
			:paginate="true"
			:server-pagination="true"
			:total-rows="totalRows"
			:pagination-size-default="paginationSizeOptions[ 0 ].value"
			:pagination-size-options="paginationSizeOptions"
			@load-more="onLoadMore"
		>
			<template #header>
				<CdxButton
					v-if="canCreateLayouts"
					@click="isCreatorOpen = true"
				>
					<CdxIcon :icon="cdxIconAdd" />
					{{ $i18n( 'neowiki-layout-creator-button' ).text() }}
				</CdxButton>
			</template>

			<template #item-name="{ item }">
				<a :href="layoutUrl( item )">{{ item }}</a>
			</template>

			<template #item-description="{ item }">
				<span
					v-if="!item"
					class="ext-neowiki-layouts-page__empty-value"
				>-</span>
				<template v-else>
					{{ item }}
				</template>
			</template>

			<template #item-schema="{ item }">
				<a :href="schemaUrl( item )">{{ item }}</a>
			</template>

			<template #empty-state>
				{{ $i18n( 'neowiki-layouts-empty' ).text() }}
			</template>
		</CdxTable>
		<LayoutCreatorDialog
			v-if="canCreateLayouts"
			:open="isCreatorOpen"
			@update:open="isCreatorOpen = $event"
			@created="fetchLayouts( 0, pageSize )"
		/>
	</div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { CdxButton, CdxIcon, CdxTable } from '@wikimedia/codex';
import type { TableColumn } from '@wikimedia/codex';
import { cdxIconAdd } from '@wikimedia/codex-icons';
import { NeoWikiExtension } from '@/NeoWikiExtension.ts';
import { useLayoutPermissions } from '@/composables/useLayoutPermissions.ts';
import LayoutCreatorDialog from './LayoutCreatorDialog.vue';

const paginationSizeOptions: { value: number }[] = [
	{ value: 10 },
	{ value: 20 },
	{ value: 50 }
];

const loading = ref( true );
const totalRows = ref( 0 );
const isCreatorOpen = ref( false );
const pageSize = ref( paginationSizeOptions[ 0 ].value );
const { canCreateLayouts, checkCreatePermission } = useLayoutPermissions();

interface LayoutRow {
	name: string;
	schema: string;
	type: string;
	description: string;
}

const rows = ref<LayoutRow[]>( [] );

const columns: TableColumn[] = [
	{
		id: 'name',
		label: mw.msg( 'neowiki-layouts-column-name' )
	},
	{
		id: 'schema',
		label: mw.msg( 'neowiki-layouts-column-schema' )
	},
	{
		id: 'type',
		label: mw.msg( 'neowiki-layouts-column-type' )
	},
	{
		id: 'description',
		label: mw.msg( 'neowiki-layouts-column-description' )
	}
];

function layoutUrl( name: string ): string {
	return mw.util.getUrl( `Layout:${ name }` );
}

function schemaUrl( name: string ): string {
	return mw.util.getUrl( `Schema:${ name }` );
}

interface LayoutSummary {
	name: string;
	schema: string;
	type: string;
	description: string;
	ruleCount: number;
}

async function fetchLayouts( offset: number, limit: number ): Promise<void> {
	loading.value = true;
	pageSize.value = limit;

	const restApiUrl = NeoWikiExtension.getInstance().getMediaWiki().util.wikiScript( 'rest' );
	const httpClient = NeoWikiExtension.getInstance().newHttpClient();

	const response = await httpClient.get(
		`${ restApiUrl }/neowiki/v0/layouts?limit=${ limit }&offset=${ offset }`
	);

	if ( !response.ok ) {
		loading.value = false;
		return;
	}

	const result: { layouts: LayoutSummary[]; totalRows: number } = await response.json();

	rows.value = result.layouts.map( ( summary ) => ( {
		name: summary.name,
		schema: summary.schema,
		type: summary.type,
		description: summary.description
	} ) );

	totalRows.value = result.totalRows;
	loading.value = false;
}

function onLoadMore( offset: number, limit: number ): void {
	fetchLayouts( offset, limit );
}

onMounted( async () => {
	await checkCreatePermission();
	await fetchLayouts( 0, paginationSizeOptions[ 0 ].value );
} );
</script>

<style lang="less">
@import ( reference ) '@wikimedia/codex-design-tokens/theme-wikimedia-ui.less';

.ext-neowiki-layouts-page {
	max-width: 64rem;

	&__empty-value {
		color: @color-subtle;
		user-select: none;
	}
}
</style>
