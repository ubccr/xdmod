import { test as base } from '@playwright/test';

export type TestOptions = {
    role: string;
}

export const test = base.extend<TestOptions>({
    role: null
});
