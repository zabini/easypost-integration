import { request } from './client';

export async function createShippingLabel(shippingLabelData) {
  const payload = await request('/shipping-labels', {
    body: shippingLabelData,
    csrf: true,
    method: 'POST',
  });

  return payload?.data || null;
}
