<?php

declare(strict_types=1);

use Database\BaseMigration;
use System\Scopes;

final class InsertDefaults extends BaseMigration
{

    public function up(): void
    {
        // Organization
        $this->sandbox(fn () => $this->table('oauth_organizations')->insert(['org_name' => getenv('COMPANY_NAME') ?? getenv('APP_NAME') ?? 'Default', 'logo' => 'public/images/logo/dark/logo_512px.png'])->saveData());
        // Scopes
        foreach (Scopes::ALL_SCOPES as $scope => $desc) {
            $this->sandbox(fn () => $this->table('oauth_scopes')->insert([
                'scope' => $scope,
                'type' => in_array($scope, Scopes::CLAIM_SCOPES) ? 'claims' : 'roles',
                'description' => $desc,
                'is_default' => $scope === Scopes::DEFAULT_SCOPE
            ])->saveData());
        }
    }

    public function down(): void
    {
        // Do nothing
    }
}
