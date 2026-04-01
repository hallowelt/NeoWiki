<template>
	<div class="ext-neowiki-display-rule-list">
		<ul
			ref="listRef"
			class="ext-neowiki-display-rule-list__items"
		>
			<li
				v-for="property in orderedProperties"
				:key="property.name.toString()"
				class="ext-neowiki-display-rule-list__item"
				:class="{ 'ext-neowiki-display-rule-list__item--enabled': isEnabled( property.name.toString() ) }"
			>
				<span
					v-if="isEnabled( property.name.toString() )"
					class="ext-neowiki-display-rule-list__item__drag-handle"
				>
					<CdxIcon
						:icon="cdxIconDraggable"
						:aria-hidden="true"
					/>
				</span>
				<CdxToggleSwitch
					:model-value="isEnabled( property.name.toString() )"
					class="ext-neowiki-display-rule-list__item__toggle"
					@update:model-value="onToggle( property.name.toString(), $event )"
				>
					{{ property.name.toString() }}
				</CdxToggleSwitch>
			</li>
		</ul>
	</div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { CdxToggleSwitch, CdxIcon } from '@wikimedia/codex';
import { cdxIconDraggable } from '@wikimedia/codex-icons';
import type { PropertyDefinition } from '@/domain/PropertyDefinition.ts';
import { PropertyName } from '@/domain/PropertyDefinition.ts';
import type { DisplayRule } from '@/domain/Layout.ts';
import { useSortable } from '@/composables/useSortable.ts';

const props = defineProps<{
	schemaProperties: PropertyDefinition[];
	displayRules: DisplayRule[];
}>();

const emit = defineEmits<{
	'update:display-rules': [ rules: DisplayRule[] ];
}>();

const listRef = ref<HTMLElement | null>( null );

const enabledNames = computed( () =>
	new Set( props.displayRules.map( ( r ) => r.property.toString() ) )
);

function isEnabled( name: string ): boolean {
	return enabledNames.value.has( name );
}

const orderedProperties = computed( () => {
	const enabled = props.displayRules
		.map( ( rule ) =>
			props.schemaProperties.find( ( p ) => p.name.toString() === rule.property.toString() )
		)
		.filter( Boolean ) as PropertyDefinition[];

	const disabled = props.schemaProperties.filter(
		( p ) => !enabledNames.value.has( p.name.toString() )
	);

	return [ ...enabled, ...disabled ];
} );

function onToggle( name: string, enabled: boolean ): void {
	let newRules: DisplayRule[];
	if ( enabled ) {
		newRules = [ ...props.displayRules, { property: new PropertyName( name ) } ];
	} else {
		newRules = props.displayRules.filter( ( r ) => r.property.toString() !== name );
	}
	emit( 'update:display-rules', newRules );
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
	&__items {
		list-style: none;
		margin: 0;
		padding: 0;
	}

	&__item {
		display: flex;
		align-items: center;
		gap: @spacing-50;
		padding: @spacing-50 @spacing-75;
		border-radius: @border-radius-base;

		&--enabled {
			cursor: grab;
		}

		&--ghost {
			opacity: 0.5;
			background-color: @background-color-interactive-subtle;
		}

		&:hover {
			background-color: @background-color-interactive-subtle;
		}

		&__drag-handle {
			min-width: @min-size-interactive-pointer;
			min-height: @min-size-interactive-pointer;
			padding-inline: @spacing-30;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			box-sizing: border-box;

			.cdx-icon {
				color: @color-placeholder;
			}
		}

		&__toggle {
			flex-grow: 1;
		}
	}
}
</style>
