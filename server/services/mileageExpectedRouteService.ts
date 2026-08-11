export interface ExpectedRouteMileageRequest {
  driverUserId: number;
  workDate: string;
  startingLocation: string;
  endingLocation: string;
  carerIds?: number[];
}

export const getExpectedRouteMileage = async (
  _request: ExpectedRouteMileageRequest,
): Promise<number | null> => {
  // TODO: Integrate Access Care Planning route order and low-mileage optimisation here.
  // For now, expected_system_mileage is entered manually by office/admin staff or imported later.
  return null;
};
