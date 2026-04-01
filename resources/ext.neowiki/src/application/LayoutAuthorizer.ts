export interface LayoutAuthorizer {

	canEditLayout( layoutName: string ): Promise<boolean>;

}
