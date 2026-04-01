import { ref, type Ref } from 'vue';
import { NeoWikiServices } from '@/NeoWikiServices.ts';

export interface LayoutPermissions {
	canEditLayout: Ref<boolean>;
	checkEditPermission: ( layoutName: string ) => Promise<void>;
}

export function useLayoutPermissions(): LayoutPermissions {
	const canEditLayout = ref( false );

	async function checkEditPermission( layoutName: string ): Promise<void> {
		try {
			canEditLayout.value = await NeoWikiServices.getLayoutAuthorizer().canEditLayout( layoutName );
		} catch ( error ) {
			console.error( 'Failed to check layout permissions:', error );
			canEditLayout.value = false;
		}
	}

	return {
		canEditLayout,
		checkEditPermission,
	};
}
