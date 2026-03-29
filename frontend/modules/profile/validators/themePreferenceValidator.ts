import * as yup from 'yup';

export const themePreferenceValidator = yup.object({
  theme: yup.mixed<'light' | 'dark'>().oneOf(['light', 'dark']).required(),
});
