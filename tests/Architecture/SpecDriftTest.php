<?php

declare(strict_types=1);

namespace OwnerPro\Asaas\Tests\Architecture;

/**
 * Spec drift guard.
 *
 * Pins the SHA256 of every per-domain spec file under `specs/domains/` to a
 * known-good revision. When the spec is updated, this test fails with a clear
 * message pointing to the re-audit procedure.
 *
 * Updating procedure (when spec drift is INTENTIONAL):
 *   1. Run the 16-dimension spec-mirroring audit.
 *   2. Open new findings under `docs/todo/X.Y-*.md`.
 *   3. Patch DTOs/Resources/Enums to reflect the new spec shape.
 *   4. Recompute SHAs:
 *      php -r "foreach(glob('specs/domains/*.json') as \$f) echo hash_file('sha256', \$f).' '.\$f.PHP_EOL;"
 *   5. Replace the EXPECTED_SHAS table below.
 *   6. Commit with message: "chore(spec): bump <domain>, closes <audit doc>".
 *
 * Do NOT update SHAs without running the audit. SHA updates without a
 * corresponding `docs/todo/X.Y-*.md` entry must be rejected in review.
 */
final class SpecDriftTest
{
    /**
     * Map of spec file path (relative to repo root) to expected SHA256.
     *
     * Snapshot captured 2026-05-12. Total: 34 files (33 domain splits + index).
     *
     * @var array<string, string>
     */
    public const EXPECTED_SHAS = [
        'specs/domains/accounts.json' => '47be6c2bd11ae576a5ae7ac66fb6fe321c07828c0897e186a2c515c5eb90326d',
        'specs/domains/anticipations.json' => 'f65b7ee6505459b72057421b23d3f21c76025f66bac0ca01a466257b3db1319b',
        'specs/domains/bill-payments.json' => '86668f9d02a3d4b34502f50d5ad4d5f88180f49f305471dd37981c32cfdded9b',
        'specs/domains/chargebacks.json' => '6803e2c35be8c79a70189dacc3f70b0e81d0c69af0f27043a280f2a0b87d0503',
        'specs/domains/checkouts.json' => '336f07c10312c9469b86a71faecf4f14efe0dddc1fefcd98b4688e33bbebad3e',
        'specs/domains/credit-bureau-report.json' => 'edf1f80598b10561e5590547bbaf8fd3adcb8845080b4e881cef68853323d9c7',
        'specs/domains/credit-card.json' => 'd0c399e4701b70bd1cc3aa2048e5cd404dea77c88a3ce31978ea0c46f6619bdb',
        'specs/domains/customers.json' => '58afcc61d8aacf0c9a24ef25921f3690c26c220c7b9aacd948328f13bc686f76',
        'specs/domains/escrow.json' => 'cbc2b3c843d26b115ffea5759fb95dbca7ebdfb929375c5070c79220f29d80f4',
        'specs/domains/finance.json' => 'ce6c36f4376a1aaa16b07644315d8a8d2c840bda501803cc413905fc29f9c203',
        'specs/domains/financial-transactions.json' => '87877c423dfc0d0c4a3d451a48dda97bc172d52f0b4931fe9c8c8e053282ae88',
        'specs/domains/fiscal-info.json' => 'f07a0dc730eca9348a0d9a892a54cfa6f9879d424eaa4267793b4070215de89f',
        'specs/domains/index.json' => '1c0b2cb15c4e2acc79e5fd211e4d4a9bd8c5a688b071d51dd5dab1217802e795',
        'specs/domains/installments.json' => 'c538e178d2f9da77e89b75b4fdd0b972459ae248d17a41a334835f942fc97a93',
        'specs/domains/invoices.json' => '09d4a30a1358eebd21a915ea315435214ea379bb692bc2f77f6f6b476e771067',
        'specs/domains/lean-payments.json' => '4787a1ae9e4a2d0db98918912e83c183baec7a1dfbc640651ef13be24e165fff',
        'specs/domains/mobile-phone-recharges.json' => '97bc3b99150f75d2162a38bc43f2a4cf685043464b89168ed56b8f2efd17025a',
        'specs/domains/my-account-documents.json' => 'e67e19c3ef22c2b9423373e6557f738018c788bcf38370a25fe61542785ecc91',
        'specs/domains/my-account.json' => '3c7fe588d74fc2f43ec7a54d99c015a11e9bac0a14b660a5f7bcff3d5aef6533',
        'specs/domains/notifications.json' => '642f1ef07cfb53ac28fda598f66eb4dfd1ee0f925d752021e03b938966efbbc4',
        'specs/domains/payment-documents.json' => 'b8615d857bf13a442876fa933c2e4ebea3a5b12f933a227c4be33599e2e9653f',
        'specs/domains/payment-dunnings.json' => '1f7463cd12ff82fe51647239991237da7add518ebd866eaf79ef7aa9afc01daf',
        'specs/domains/payment-links.json' => '61b7e3aca10d170ececb472ef987242fa27df733f49386576012fd185bae0dc1',
        'specs/domains/payment-refunds.json' => '77ccc4af8a4640ab21acb657c57259ab2f89da404a11bee7a3a9f658fafcc19b',
        'specs/domains/payment-splits.json' => '2073e5fafc9182d9de2612d69c820dfc399ef5b6c92e56b2d8197ecbf728dadf',
        'specs/domains/payments.json' => 'cd5c3a49785f8547e92ff6137db62cfa96bbf06a63438805d50685b42cdecb7a',
        'specs/domains/pix-automatic.json' => '241d46e579eb013f6353cb5ad5295170d4423188851d5a535dc569d0517e16d1',
        'specs/domains/pix-recurring.json' => '25a80471511d94d9237aa5842684af10d6e67327378b078c9742668016393a81',
        'specs/domains/pix-transactions.json' => '4a23b5c47b70d1c47ea30b7c4044df97a61df9d43ab5aae1ac25773b3d1c6cb2',
        'specs/domains/pix.json' => 'df522525a26db6fe636d9f780225be36fb30e5a1fcb45d2215af2b67450d024d',
        'specs/domains/sandbox.json' => 'cfeb8d8fe20f4cf645a6a0150e1bde548f304809f3d4aee5338bcda2705bf967',
        'specs/domains/subscriptions.json' => '84f64adbef3faf458fb49e10c56844a55700b5d5541c16193f20397b872a122a',
        'specs/domains/transfers.json' => 'bd119dd668f4d0b78b4587cedcbe6743bc9129f7401f0d7eaec7168b88b9665f',
        'specs/domains/webhooks.json' => 'f9fbfd8943b42f4e62560d758f47526b738db7d060c39e33b3179aae730a23db',
    ];

    public static function repoRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}

it('pins SHA256 of every per-domain spec file', function (): void {
    $root = SpecDriftTest::repoRoot();
    $drifts = [];

    foreach (SpecDriftTest::EXPECTED_SHAS as $relPath => $expected) {
        $abs = $root.'/'.$relPath;
        expect(is_file($abs))->toBeTrue("spec file missing: {$relPath}");

        $actual = hash_file('sha256', $abs);
        if ($actual !== $expected) {
            $drifts[] = sprintf('  %s: expected %s, got %s', $relPath, $expected, $actual);
        }
    }

    expect($drifts)->toBe(
        [],
        "Spec drift detected.\n"
        ."Updating SHAs requires a full re-audit (see docs/todo/2.2-drift-guard.md).\n"
        ."Drifted files:\n".implode("\n", $drifts),
    );
});

it('lists no orphan spec files (every spec file is pinned)', function (): void {
    $root = SpecDriftTest::repoRoot();
    $glob = glob($root.'/specs/domains/*.json') ?: [];

    $relative = array_map(static fn (string $abs): string => substr($abs, strlen($root) + 1), $glob);
    $pinned = array_keys(SpecDriftTest::EXPECTED_SHAS);
    $orphans = array_values(array_diff($relative, $pinned));

    expect($orphans)->toBe(
        [],
        "Spec files not pinned in SpecDriftTest::EXPECTED_SHAS:\n  ".implode("\n  ", $orphans),
    );
});

it('pins exactly 34 spec files (33 domain splits + index)', function (): void {
    expect(SpecDriftTest::EXPECTED_SHAS)->toHaveCount(34);
});
