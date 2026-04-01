import type { RightsFetcher } from '@/persistence/UserObjectBasedRightsFetcher';
import type { LayoutAuthorizer } from '@/application/LayoutAuthorizer';

export class RightsBasedLayoutAuthorizer implements LayoutAuthorizer {

	public constructor( private readonly rightsFetcher: RightsFetcher ) {
	}

	public async canEditLayout( _layoutName: string ): Promise<boolean> {
		const rights = await this.rightsFetcher.getRights();
		return rights.includes( 'neowiki-layout-edit' );
	}

}
