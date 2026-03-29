import * as yup from 'yup';

export const observabilityFilterValidator = yup.object({
  period_minutes: yup.number().integer().min(5).max(1440).required(),
});
