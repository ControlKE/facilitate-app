import axios from 'axios';
import { buildPhpApiUrl } from '../utils/phpApi';

const http = axios.create({ withCredentials: false });

// Public, unauthenticated submission from the driver-facing mileage area.
// Always sent as multipart/form-data (even for single-day entries) so the
// same endpoint transparently supports the optional photo attachment.
export const submitDriverMileage = async (formData) => {
  const response = await http.post(buildPhpApiUrl('submitMileageForm', 'submit'), formData);
  const data = response?.data || {};
  if (!data.success) {
    // debugError/debugErrorFile are only ever populated for localhost dev
    // origins (see submitMileageForm.php) -- safe to surface in the UI.
    const detail = data.debugError ? ` [${data.debugError}${data.debugErrorFile ? ' @ ' + data.debugErrorFile : ''}]` : '';
    throw new Error((data.message || 'Failed to submit mileage.') + detail);
  }
  return data;
};
