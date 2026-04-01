export interface LayoutAuthorizer {

	canCreateLayouts(): Promise<boolean>;

	canEditLayout( layoutName: string ): Promise<boolean>;

}
