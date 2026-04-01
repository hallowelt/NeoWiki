<template>
	<div class="ext-neowiki-display-rule-list">
		<div class="ext-neowiki-display-rule-list__section-header">
			{{ $i18n( 'neowiki-layout-editor-shown-properties', enabledCount ).text() }}
		</div>
		<ul
			ref="listRef"
			class="ext-neowiki-display-rule-list__items"
		>
			<li
				v-for="property in enabledProperties"
				:key="property.name.toString()"
				class="ext-neowiki-display-rule-list__item ext-neowiki-display-rule-list__item--enabled"
				@click="onHide( property.name.toString() )"
			>
				<CdxIcon
					v-if="getPropertyType( property )"
					:icon="getIcon( getPropertyType( property )! )"
					:title="getTypeLabel( getPropertyType( property )! )"
					class="ext-neowiki-display-rule-list__item__type-icon"
				/>
				<span class="ext-neowiki-display-rule-list__item__name">
					{{ property.name.toString() }}
				</span>
				<span class="ext-neowiki-display-rule-list__item__drag-handle">
					<CdxIcon
						:icon="cdxIconDraggable"
						:aria-hidden="true"
					/>
				</span>
			</li>
		</ul>
		<p
			v-if="enabledCount === 0"
			class="ext-neowiki-display-rule-list__empty"
		>
			{{ $i18n( 'neowiki-layout-display-no-rules' ).text() }}
		</p>

		<div
			v-if="disabledCount > 0"
			class="ext-neowiki-display-rule-list__section-header"
		>
			{{ $i18n( 'neowiki-layout-editor-hidden-properties', disabledCount ).text() }}
		</div>
		<ul
			v-if="disabledCount > 0"
			class="ext-neowiki-display-rule-list__items"
		>
			<li
				v-for="property in disabledProperties"
				:key="property.name.toString()"
				class="ext-neowiki-display-rule-list__item"
				@click="onShow( property.name.toString() )"
			>
				<CdxIcon
					v-if="getPropertyType( property )"
					:icon="getIcon( getPropertyType( property )! )"
					:title="getTypeLabel( getPropertyType( property )! )"
					class="ext-neowiki-display-rule-list__item__type-icon"
				/>
				<span class="ext-neowiki-display-rule-list__item__name">
					{{ property.name.toString() }}
				</span>
			</li>
		</ul>
	</div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { CdxIcon } from '@wikimedia/codex';
import { cdxIconDraggable } from '@wikimedia/codex-icons';
import type { Icon } from '@wikimedia/codex-icons';
import type { PropertyDefinition } from '@/domain/PropertyDefinition.ts';
import { PropertyName } from '@/domain/PropertyDefinition.ts';
import type { DisplayRule } from '@/domain/Layout.ts';
import { NeoWikiServices } from '@/NeoWikiServices.ts';
import { useSortable } from '@/composables/useSortable.ts';

const props = defineProps<{
	schemaProperties: PropertyDefinition[];
	displayRules: DisplayRule[];
}>();

const emit = defineEmits<{
	'update:display-rules': [ rules: DisplayRule[] ];
}>();

const listRef = ref<HTMLElement | null>( null );
const componentRegistry = NeoWikiServices.getComponentRegistry();

const enabledNames = computed( () =>
	new Set( props.displayRules.map( ( r ) => r.property.toString() ) )
);

const enabledProperties = computed( () =>
	props.displayRules
		.map( ( rule ) =>
			props.schemaProperties.find( ( p ) => p.name.toString() === rule.property.toString() )
		)
		.filter( Boolean ) as PropertyDefinition[]
);

const disabledProperties = computed( () =>
	props.schemaProperties.filter(
		( p ) => !enabledNames.value.has( p.name.toString() )
	)
);

const enabledCount = computed( () => enabledProperties.value.length );
const disabledCount = computed( () => disabledProperties.value.length );

function getPropertyType( property: PropertyDefinition ): string | undefined {
	return property.type;
}

function getIcon( propertyType: string ): Icon {
	return componentRegistry.getIcon( propertyType );
}

function getTypeLabel( propertyType: string ): string {
	return mw.msg( componentRegistry.getLabel( propertyType ) );
}

function onShow( name: string ): void {
	emit( 'update:display-rules', [ ...props.displayRules, { property: new PropertyName( name ) } ] );
}

function onHide( name: string ): void {
	emit( 'update:display-rules', props.displayRules.filter( ( r ) => r.property.toString() !== name ) );
}

useSortable( listRef, {
	handle: '.ext-neowiki-display-rule-list__item__drag-handle',
	ghostClass: 'ext-neowiki-display-rule-list__item--ghost',
	onReorder( oldIndex: number, newIndex: number ): void {
		const enabledRules = [ ...props.displayRules ];
		const [ moved ] = enabledRules.splice( oldIndex, 1 );
		enabledRules.splice( newIndex, 0, moved );
		emit( 'update:display-rules', enabledRules );
	}
} );
</script>

<style lang="less">
@import ( reference ) '@wikimedia/codex-design-tokens/theme-wikimedia-ui.less';

.ext-neowiki-display-rule-list {
	&__section-header {
		font-size: @font-size-small;
		color: @color-subtle;
		padding: @spacing-75 @spacing-75 @spacing-25;
	}

	&__items {
		list-style: none;
		margin: 0;
		padding: 0;
	}

	&__empty {
		color: @color-subtle;
		padding: @spacing-50 @spacing-75;
		font-size: @font-size-small;
	}

	&__item {
		display: flex;
		align-items: center;
		gap: @spacing-50;
		padding: @spacing-50 @spacing-75;
		border-radius: @border-radius-base;
		cursor: pointer;
		user-select: none;

		&--ghost {
			opacity: 0.5;
			background-color: @background-color-interactive-subtle;
		}

		&:hover {
			background-color: @background-color-interactive-subtle;
		}

		&__type-icon {
			color: @color-subtle;
			flex-shrink: 0;
		}

		&__name {
			flex-grow: 1;
		}

		&__drag-handle {
			min-width: @min-size-interactive-pointer;
			min-height: @min-size-interactive-pointer;
			padding-inline: @spacing-30;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			box-sizing: border-box;
			opacity: 0;
			transition: opacity @transition-duration-medium @transition-timing-function-system;
			cursor: grab;

			.ext-neowiki-display-rule-list__item:hover &,
			.ext-neowiki-display-rule-list__item:focus-within & {
				opacity: 1;
			}

			.cdx-icon {
				color: @color-placeholder;
			}
		}
	}
}
</style>
