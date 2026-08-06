// Stands in for a real Vitest suite for the translation composable: every call here is an
// assertion input, not a string any user sees.
describe('useTranslations', () => {
    it('returns the key when nothing is registered', () => {
        expect(__('Excluded fixture string')).toBe('Excluded fixture string');
    });

    it('resolves a dotted key', () => {
        expect(trans('excluded.fixture.key')).toBe('whatever');
    });
});
